<?php

namespace JoshEmbling\Laragenie\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JoshEmbling\Laragenie\Helpers;
use JoshEmbling\Laragenie\Models\Laragenie as LaragenieModel;
use OpenAI;
use Probots\Pinecone\Client as Pinecone;

use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;

class LaragenieCommand extends Command
{
    use Helpers\Actions, Helpers\Calculations;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragenie';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A friendly bot to help you with code in your Laravel project.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $openai = OpenAI::client(env('OPENAI_API_KEY'));
        $pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENVIRONMENT'));

        match ($this->welcome()) {
            'q' => $this->askQuestion($openai, $pinecone),
            'i' => $this->getFilesToIndex($openai, $pinecone),
            'r' => $this->removeIndexedFiles($openai, $pinecone),
            'o' => $this->somethingElse($openai, $pinecone),
        };
    }

    public function askQuestion(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $user_question = $this->ask('What is your question for '.config('laragenie.bot.name'));

        if (! $user_question) {
            $this->error('You must provide a question.');

            $this->userAction($openai, $pinecone);
        } else {
            $this->userQuestion($openai, $pinecone, $user_question);
        }
    }

    public function userQuestion(OpenAI\Client $openai, Pinecone $pinecone, string $user_question)
    {
        $question = Str::lower($user_question);

        $ai = Str::endsWith($question, '--ai');

        $formattedQuestion = $ai ? Str::remove('--ai', $question) : $question;

        if (config('laragenie.database.fetch') || config('laragenie.database.save')) {
            $laragenie = LaragenieModel::firstOrNew([
                'question' => $formattedQuestion,
            ]);
        }

        if ($laragenie->exists && config('laragenie.database.fetch') && ! $ai) {
            $this->info($laragenie->answer);
        } else {
            $this->question('Asking '.config('laragenie.bot.name').", '{$question}'...");

            $questionResponse = $this->askBot($openai, $pinecone, $formattedQuestion);

            $botResponse = $this->botResponse($openai, $questionResponse['data'], $question);

            if ($botResponse) {
                $answer = $botResponse->choices[0]->message->content;
                $tokens = $botResponse->usage->totalTokens;
                $calculatedCost = $this->calculateCost($tokens);

                if (config('laragenie.database.save')) {
                    $laragenie->fill([
                        'answer' => $answer,
                        'cost' => $calculatedCost,
                        'vectors' => $questionResponse['vectors'],
                    ]);

                    $laragenie->save();
                }

                $this->info($answer);
                $this->costResponse($calculatedCost);
            }
        }

        $this->userAction($openai, $pinecone);
    }

    public function askBot(OpenAI\Client $openai, Pinecone $pinecone, string $question)
    {
        // Use OpenAI to generate context
        $openai_res = $openai->embeddings()->create([
            'model' => config('laragenie.openai.embedding.model'),
            'input' => $question,
            'max_tokens' => config('laragenie.openai.embedding.max_tokens'),
        ]);

        $pinecone_res = $pinecone->index(env('PINECONE_INDEX'))->vectors()->query(
            vector: $openai_res->embeddings[0]->toArray()['embedding'],
            topK: config('laragenie.pinecone.topK'),
        );

        if (empty($pinecone_res->json()['matches'])) {
            $this->error('There are no indexed files.');
            exit();
        }

        return [
            'data' => $pinecone_res->json()['matches'][0]['metadata']['text'],
            'vectors' => $openai_res->embeddings[0]->toArray()['embedding'],
        ];
    }

    public function botResponse(OpenAI\Client $openai, $chunks, string $question)
    {
        $this->newLine();
        $this->line('Generating answer...');
        $this->newLine();

        try {
            $response = spin(
                fn () => $openai->chat()->create([
                    'model' => config('laragenie.openai.chat.model'),
                    'temperature' => config('laragenie.openai.chat.temperature'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => config('laragenie.bot.instruction').$chunks,
                        ],
                        [
                            'role' => 'user',
                            'content' => $question,
                        ],
                    ],
                ])
            );
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            exit();
        }

        return $response;
    }

    public function getFilesToIndex(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $user_path = $this->ask('Enter your file path');

        if (! $user_path) {
            $this->error('You must provide a path.');

            $this->userAction($openai, $pinecone);
        }

        $files = glob($user_path);

        if (! $files) {
            $this->error("No files found at {$user_path}");

            $this->userAction($openai, $pinecone);
        }

        $this->line('Indexing files...');

        foreach ($files as $file) {
            $this->warn('Indexing "'.$file.'" ...');

            $contents = file_get_contents($file);

            $chunk_contents = str_split($contents, config('laragenie.chunks.size'));

            $chunks = array_map(function ($chunk) use ($file) {
                return "Title: {$file} {$chunk}";
            }, $chunk_contents);

            $this->indexFiles($chunks, $file, $openai, $pinecone);

            $this->info($file.' finished indexing');
            $this->newLine();
        }

        $this->newLine();
        $this->info('All files have been indexed! 🎉');
        $this->newLine();

        $this->userAction($openai, $pinecone);
    }

    public function indexFiles(array $chunks, string $file, OpenAI\Client $openai, Pinecone $pinecone)
    {
        foreach ($chunks as $idx => $chunk) {
            $vector_response = $openai->embeddings()->create([
                'model' => config('laragenie.openai.embedding.model'),
                'input' => $chunk,
            ]);

            if ($vector_response) {
                $pinecone_upsert = $pinecone->index(env('PINECONE_INDEX'))->vectors()->upsert(vectors: [
                    'id' => str_replace('/', '-', $file).'-'.$idx,
                    //'values' => array_fill(0, 1536, 0.14),
                    'values' => $vector_response->embeddings[0]->toArray()['embedding'],
                    'metadata' => [
                        'filename' => $file,
                        'text' => $chunk,
                    ],
                ]);
            }
        }
    }

    public function removeIndexedFiles(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $remove = $this->removeAction();

        if ($remove === 'all') {
            $confirm = $this->removeAllActionConfirm();

            if ($confirm === 'y') {
                $this->flushFiles($openai, $pinecone);
            } else {
                $this->userAction($openai, $pinecone);
            }
        }

        $files = $this->ask('What file(s) do you want to remove from your index? (You can provide the full namespace and file extension or a directory with a wildcard)');

        if (! $files) {
            $this->error('You must provide a filename.');

            $this->userAction($openai, $pinecone);
        }

        $this->question('Finding vectors...');

        $this->findFilesToRemove($pinecone, $files);

        $this->userAction($openai, $pinecone);
    }

    public function findFilesToRemove(Pinecone $pinecone, string $user_files)
    {
        $files = glob($user_files);

        foreach ($files as $file) {
            $formatted_filename = str_replace('/', '-', $file);

            $this->warn('Attempting to remove all "'.$file.'" indexes...');

            for ($i = 0; $i < 100; $i++) {
                try {
                    $pinecone_res = $pinecone->index(env('PINECONE_INDEX'))->vectors()->fetch([
                        "{$formatted_filename}-{$i}",
                    ]);
                } catch (\Throwable $th) {
                    $this->error('There has been an error.');
                    break;
                }

                if ($i === 0 && empty($pinecone_res->json()['vectors'])) {
                    $this->warn('No indexes were found for the file '.$file);
                    break;
                }

                if (config('laragenie.indexes.removal.strict') && $i === 0) {
                    $choice = select(
                        'Vectors have been found, are you sure you want to delete them? 🤔',
                        [
                            'y' => 'Yes',
                            'n' => 'No',
                        ],
                    );

                    if ($choice === 'y') {
                        $this->question("Alright, let's bin those 🚽");
                    } else {
                        $this->info('Nothing has been deleted 😅');
                        break;
                    }
                }

                try {
                    $response = $pinecone->index(env('PINECONE_INDEX'))->vectors()->delete(
                        ids: ["{$formatted_filename}-{$i}"],
                        deleteAll: false
                    );
                } catch (\Throwable $th) {
                    $this->error($th);
                }

                if (empty($pinecone_res->json()['vectors'])) {
                    $this->info('Vectors have been deleted that were associated with '.$file);
                    $this->newLine();

                    break;
                }
            }
        }
    }

    public function flushFiles(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $pinecone->index(env('PINECONE_INDEX'))->vectors()->delete(
            deleteAll: true
        );

        $this->info('All files have been removed.');

        $this->userAction($openai, $pinecone);
    }

    public function somethingElse(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $this->info('You can contact @joshembling on Github to suggest another feature.');

        $this->userAction($openai, $pinecone);
    }
}
