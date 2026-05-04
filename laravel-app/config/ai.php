<?php
return [

    'default' => 'ollama',

    'api_key' => env('AI_API_KEY'),

    // Ollama inference limits — lower values = less RAM and CPU on local machines
    'num_ctx'     => env('OLLAMA_NUM_CTX', 2048),
    'num_predict' => env('OLLAMA_NUM_PREDICT', 512),

    'providers' => [

        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'),
            'models' => [
                'phi'    => 'phi:latest',
                'llama3' => 'llama3:latest',
                'gemma2' => 'gemma2:latest',
                'mistral'=> 'mistral:latest',
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