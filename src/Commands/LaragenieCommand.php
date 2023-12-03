<?php

namespace JoshEmbling\Laragenie\Commands;

use Illuminate\Console\Command;
use JoshEmbling\Laragenie\Helpers;
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
            'q' => $this->userQuestion($openai, $pinecone),
            'i' => $this->getFilesToIndex($openai, $pinecone),
            'r' => $this->removeIndexedFiles($openai, $pinecone),
            'o' => $this->somethingElse($openai, $pinecone),
        };
    }

    public function userQuestion(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $user_question = $this->ask('What is your question for '.config('laragenie.bot.name'));

        if (! $user_question) {
            $this->error('You must provide a question.');

            $this->userAction($openai, $pinecone);
        }

        $question = $user_question;

        $this->question("Asking BM Bot, '{$question}'...");

        $getChunks = $this->askBot($openai, $pinecone, $question);
        $response = $this->botResponse($openai, $getChunks, $question);

        $this->info($response->choices[0]->message->content);

        $this->calculateCost($response->usage->totalTokens);

        $this->userAction($openai, $pinecone);
    }

    public function askBot(OpenAI\Client $openai, Pinecone $pinecone, string $question)
    {
        // Use OpenAI to generate context
        $openai_res = $openai->embeddings()->create([
            'model' => 'text-embedding-ada-002',  // Use an appropriate model
            'input' => $question,
            'max_tokens' => 5,  // Adjust as needed
        ]);

        $pinecone_res = $pinecone->index(env('PINECONE_INDEX'))->vectors()->query(
            vector: $openai_res->embeddings[0]->toArray()['embedding'],
            topK: 2,
        );

        if (empty($pinecone_res->json()['matches'])) {
            $this->error('There are no indexed files.');
            exit();
        }

        // Get the matching document's text from the metadata
        return $pinecone_res->json()['matches'][0]['metadata']['text'];
    }

    public function botResponse(OpenAI\Client $openai, $chunks, string $question)
    {
        $this->newLine();
        $this->line('Generating answer...');
        $this->newLine();

        try {
            $response = spin(
                fn () => $openai->chat()->create([
                    'model' => 'gpt-4-1106-preview',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Write only in markdown format. Only write factual data that can be pulled from indexed chunks. If the user ever refers to "Brass Monkey", assume this is the name of the project. These are your relevant chunks: '.$chunks,
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
            $contents = file_get_contents($file);

            $chunk_contents = str_split($contents, 1000);

            $chunks = array_map(function ($chunk) use ($file) {
                return "Title: {$file} {$chunk}";
            }, $chunk_contents);

            $this->indexFiles($chunks, $file, $openai, $pinecone);
        }

        $this->newLine();
        $this->info('Files have been indexed!');
        $this->newLine();

        $this->userAction($openai, $pinecone);
    }

    public function indexFiles(array $chunks, string $file, OpenAI\Client $openai, Pinecone $pinecone)
    {
        foreach ($chunks as $idx => $chunk) {
            $vector_response = $openai->embeddings()->create([
                'model' => 'text-embedding-ada-002',
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
        $file = $this->ask('What file do you want to remove from your index? (You must provide the full namespace and file extension)');

        if (! $file) {
            $this->error('You must provide a filename.');

            $this->userAction($openai, $pinecone);
        }

        $formatted_filename = str_replace('/', '-', $file);

        $this->question('Finding vectors...');

        $this->findFilesToRemove($pinecone, $file, $formatted_filename);

        $this->userAction($openai, $pinecone);
    }

    public function findFilesToRemove(Pinecone $pinecone, string $file, string $formatted_filename)
    {
        for ($i = 1; $i < 100; $i++) {
            try {
                $pinecone_res = $pinecone->index(env('PINECONE_INDEX'))->vectors()->fetch([
                    "{$formatted_filename}-{$i}",
                ]);
            } catch (\Throwable $th) {
                $this->error('There has been an error.');
                break;
            }

            if ($i === 1 && empty($pinecone_res->json()['vectors'])) {
                $this->warn('No indexes were found for the file '.$file);
                break;
            } elseif ($i === 1) {
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
                $this->newLine();
                $this->info('Vectors have been deleted that were associated with '.$file);

                break;
            }
        }
    }

    public function somethingElse(OpenAI\Client $openai, Pinecone $pinecone)
    {
        $this->info('You can email josh.embling@parall.ax to suggest another feature.');

        $this->userAction($openai, $pinecone);
    }
}
