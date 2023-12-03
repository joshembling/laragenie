<?php

namespace JoshEmbling\Laragenie\Helpers;

use OpenAI;
use Probots\Pinecone\Client as Pinecone;

use function Laravel\Prompts\select;

trait Actions
{
    public function welcome()
    {
        $this->newLine();
        $this->warn('Hello, I am '.config('laragenie.bot.name').', how may I assist you today?');
        $this->newLine();

        sleep(1);

        return select(
            'What do you want to do? ',
            [
                'q' => 'Ask a question 🙋‍♂️',
                'i' => 'Index files 🗂',
                'r' => 'Remove indexed files 🚽',
                'o' => 'Something else 🤔',
            ],
        );
    }

    public function userAction(OpenAI\Client $openai, Pinecone $pinecone)
    {
        sleep(1);

        $choice = select(
            'Do you want to do something else?',
            [
                'x' => 'No thanks, goodbye! 👋 ',
                'q' => 'Ask a question 🙋‍♂️',
                'i' => 'Index files 🗂',
                'r' => 'Remove indexed files 🚽',
            ],
        );

        match ($choice) {
            'q' => $this->userQuestion($openai, $pinecone),
            'i' => $this->getFilesToIndex($openai, $pinecone),
            'r' => $this->removeIndexedFiles($openai, $pinecone),
            'x' => exit(),
        };
    }
}
