<?php
return [

    'default' => 'ollama',

    'providers' => [

        'ollama' => [
            'url' => 'http://127.0.0.1:11434/api/generate',
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