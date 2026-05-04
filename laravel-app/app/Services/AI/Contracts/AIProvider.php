<?php
namespace App\Services\AI\Contracts;

interface AIProvider
{
    public function generate(string $prompt, string $model): array;
    public function stream(string $prompt, string $model): mixed;
}