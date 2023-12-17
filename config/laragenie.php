<?php

// config for JoshEmbling/Laragenie
return [
    'bot' => [
        'name' => 'Laragenie',
        'instructions' => 'Write only in markdown format. Only write factual data that can be pulled from indexed chunks.',
    ],

    'database' => [
        'fetch' => true,
        'save' => true,
    ],

    'openai' => [
        'embedding' => [
            'model' => 'text-embedding-ada-002',
            'max_tokens' => 5,
        ],
        'chat' => [
            'model' => 'gpt-4-1106-preview',
            'temperature' => 0.1,
        ],
    ],

    'pinecone' => [
        'topK' => 2,
    ],

    'chunks' => [
        'size' => 1000,
    ],
];
