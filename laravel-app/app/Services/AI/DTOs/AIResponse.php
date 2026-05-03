<?php
namespace App\Services\AI\DTOs;

class AIResponse
{
    public function __construct(
        public bool $success,
        public string $model,
        public string $message,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'model' => $this->model,
            'message' => $this->message,
        ];
    }
}