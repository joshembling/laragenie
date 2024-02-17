<?php

namespace JoshEmbling\Laragenie\Helpers;

use Illuminate\Support\Str;
use JoshEmbling\Laragenie\Models\Laragenie as LaragenieModel;

trait Questions
{
    use Chatbot;

    public function userQuestion(string $user_question): void
    {
        $question = Str::lower($user_question);
        $ai = Str::endsWith($question, '--ai');

        if ($ai) {
            $formattedQuestion = trim(Str::remove('--ai', $question));
        } else {
            $formattedQuestion = trim($question);
        }

        if (config('laragenie.database.fetch') || config('laragenie.database.save')) {
            $laragenie = LaragenieModel::firstOrNew([
                'question' => $formattedQuestion,
            ]);
        }

        if ($laragenie->exists && config('laragenie.database.fetch') && ! $ai) {
            $this->textOutput($laragenie->answer);
        } else {
            $results = (array) $this->askBot($formattedQuestion);
            $embeddings = [];

            foreach ($results['data'] as $key => $result) {
                $embeddings[$key] = $result['metadata']['text'];
            }

            $chunks = implode(',', $embeddings);

            $botResponse = $this->botResponse($chunks, $question);

            if ($botResponse) {
                $answer = $botResponse->choices[0]->message->content;
                $tokens = $botResponse->usage->totalTokens;
                $calculatedCost = $this->calculateCost($tokens);

                if (config('laragenie.database.save')) {
                    $laragenie->fill([
                        'answer' => $answer,
                        'cost' => $calculatedCost,
                        'vectors' => $results['vectors'],
                    ]);

                    $laragenie->save();
                }

                $this->textOutput($answer);
                $this->costResponse($calculatedCost);
            }
        }

        $this->userAction();
    }
}
