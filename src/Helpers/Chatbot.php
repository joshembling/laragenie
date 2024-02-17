<?php

namespace JoshEmbling\Laragenie\Helpers;

use OpenAI\Responses\Chat\CreateResponse;

use function Laravel\Prompts\spin;

trait Chatbot
{
    use Actions;

    public function askBot(string $question): array
    {
        // Use OpenAI to generate context
        $openai_res = $this->openai->embeddings()->create([
            'model' => config('laragenie.openai.embedding.model'),
            'input' => $question,
            'max_tokens' => config('laragenie.openai.embedding.max_tokens'),
        ]);
        $pinecone_res = $this->pinecone->index(env('PINECONE_INDEX'))->vectors()->query(
            vector: $openai_res->embeddings[0]->toArray()['embedding'],
            topK: config('laragenie.pinecone.topK'),
        );

        if (empty($pinecone_res->json()['matches'])) {
            $this->textError('There are no indexed files.');
            $this->userAction();
        }

        return [
            'data' => $pinecone_res->json()['matches'],
            'vectors' => $openai_res->embeddings[0]->toArray()['embedding'],
        ];
    }

    public function botResponse(string $chunks, string $question): CreateResponse
    {
        $this->textNote('Generating answer...');

        try {
            $response = spin(
                fn () => $this->openai->chat()->create([
                    'model' => config('laragenie.openai.chat.model'),
                    'temperature' => config('laragenie.openai.chat.temperature'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => config('laragenie.bot.instructions').$chunks,
                        ],
                        [
                            'role' => 'user',
                            'content' => $question,
                        ],
                    ],
                ])
            );
        } catch (\Throwable $th) {
            $this->textError($th->getMessage());
            $this->exitCommand();
        }

        return $response;
    }
}
