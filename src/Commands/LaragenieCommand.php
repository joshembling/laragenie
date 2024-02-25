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

    public OpenAI\Client $openai;

    public Pinecone $pinecone;

    public function __construct()
    {
        parent::__construct();

        $this->openai = OpenAI::client(env('OPENAI_API_KEY') ?? '');
        $this->pinecone = new Pinecone(env('PINECONE_API_KEY') ?? '', env('PINECONE_INDEX_HOST') ?? '');
    }

    public function handle()
    {
        match ($this->welcome()) {
            'q' => $this->askQuestion(),
            'i' => $this->askToIndex(),
            'r' => $this->askToRemoveIndexes(),
            'o' => $this->somethingElse(),
        };

        return Command::SUCCESS;
    }

    public function askQuestion(): void
    {
        $user_question = text('What is your question for '.config('laragenie.bot.name'));

        if (! $user_question) {
            $this->textError('You must provide a question.');
            $this->userAction();
        } else {
            $this->userQuestion($user_question);
        }
    }

    public function askToIndex(): void
    {
        $index_action = $this->indexAction();

        $user_path = null;

        if ($index_action === 'y') {
            if (config('laragenie.indexes.directories') && is_array(config('laragenie.indexes.directories'))) {
                $user_path = implode(',', config('laragenie.indexes.directories')).',';
            }

            if (config('laragenie.indexes.files') && is_array(config('laragenie.indexes.files'))) {
                $user_path .= implode(',', config('laragenie.indexes.files'));
            }

            if (! config('laragenie.indexes.directories') && ! config('laragenie.indexes.files')) {
                $this->textError('No directories or files were found in your indexes config.');
                $this->userAction();
            }
        } else {
            $user_path = text('Enter your file path(s)');

            if (! $user_path) {
                $this->textError('You must provide at least one directory or file name.');

                $this->userAction();
            }
        }

        $directories_and_files = $this->getDirectoriesAndFiles($user_path);

        $this->getFilesToIndex($directories_and_files);
    }

    public function askToRemoveIndexes(): void
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

        $paths = text(
            label: 'What file(s) do you want to remove from your index?',
            placeholder: 'E.g. App/Models, tests/Feature/MyTest.php',
            hint: 'Comma seperated list of singular files, multiple directories etc.'
        );

        if (! $paths) {
            $this->textError('You must provide a valid filename or directory.');
            $this->userAction();
        }

        $this->question('Finding vectors...');

        $this->removeIndexedFiles($paths);

        $this->userAction();
    }

    public function somethingElse(): void
    {
        $this->textOutput('You can contact @joshembling on Github to suggest another feature.');
        $this->userAction();
    }
}
