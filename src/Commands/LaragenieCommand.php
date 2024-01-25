<?php

namespace JoshEmbling\Laragenie\Commands;

use Illuminate\Console\Command;
use JoshEmbling\Laragenie\Helpers;
use OpenAI;
use Probots\Pinecone\Client as Pinecone;

use function Laravel\Prompts\text;

class LaragenieCommand extends Command
{
    use Helpers\Actions, Helpers\Calculations, Helpers\Formatting, Helpers\Indexes, Helpers\Questions;

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

    public $openai;

    public $pinecone;

    public function __construct()
    {
        parent::__construct();

        $this->openai = OpenAI::client(env('OPENAI_API_KEY'));
        $this->pinecone = new Pinecone(env('PINECONE_API_KEY'), env('PINECONE_ENVIRONMENT'));
    }

    public function handle()
    {
        match ($this->welcome()) {
            'q' => $this->askQuestion(),
            'i' => $this->askToIndex(),
            'r' => $this->askToRemoveIndexes(),
            'o' => $this->somethingElse(),
        };
    }

    public function askQuestion()
    {
        $user_question = text('What is your question for '.config('laragenie.bot.name'));

        if (! $user_question) {
            $this->textError('You must provide a question.');

            $this->userAction();
        } else {
            $this->userQuestion($user_question);
        }
    }

    public function askToIndex()
    {
        $user_path = $this->getFilesToIndex();
        $directories_and_files = $this->getDirectoriesAndFiles($user_path);

        $this->textNote('Indexing files...');

        foreach ($directories_and_files as $dir_file) {

            foreach ($dir_file as $file) {
                $this->textWarning('Indexing "'.$file.'"...');

                $contents = file_get_contents($file);
                $chunk_contents = str_split($contents, config('laragenie.chunks.size'));

                $chunks = array_map(function ($chunk) use ($file) {
                    return "Title: {$file} {$chunk}";
                }, $chunk_contents);

                $this->indexFiles($chunks, strtolower($file));

                $this->textOutput($file.' finished indexing');
                $this->newLine();
            }
        }

        $this->textOutput('-------------------------------');
        $this->textOutput('All files have been indexed! 🎉');
        $this->newLine();

        $this->userAction();
    }

    public function askToRemoveIndexes()
    {
        $remove = $this->removeAction();

        if ($remove === 'all') {
            $confirm = $this->removeAllActionConfirm();

            if ($confirm === 'y') {
                $this->flushFiles();
            } else {
                $this->textOutput('No files have been deleted.');
                $this->userAction();
            }
        }

        $paths = text('What file(s) do you want to remove from your index? (You can provide a singular files, or a comma separated list of multiple directories)');

        if (! $paths) {
            $this->textError('You must provide a valid filename or directory.');
            $this->userAction();
        }

        $this->question('Finding vectors...');

        $this->removeIndexedFiles($paths);

        $this->userAction();
    }

    public function somethingElse()
    {
        $this->textOutput('You can contact @joshembling on Github to suggest another feature.');
        $this->userAction();
    }
}
