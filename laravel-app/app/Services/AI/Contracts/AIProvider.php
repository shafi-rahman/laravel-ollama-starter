<?php
namespace App\Services\AI\Contracts;

interface AIProvider
{
    public function generate(string $prompt, string $model): array;
}