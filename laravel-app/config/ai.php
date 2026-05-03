<?php
return [
    'provider' => env('AI_PROVIDER', 'ollama'),

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://localhost:11434/api/generate'),
        'default_model' => env('OLLAMA_MODEL', 'phi'),
    ],
];