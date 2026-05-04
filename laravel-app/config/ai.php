<?php
return [

    'default' => 'ollama',

    'api_key' => env('AI_API_KEY'),

    'providers' => [

        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'),
            'models' => [
                'phi' => 'phi:latest',
                'llama3' => 'llama3:latest',
            ],
        ],

        // future
        'openai' => [
            'models' => [
                'gpt4' => 'gpt-4o',
            ],
        ],

    ],
];