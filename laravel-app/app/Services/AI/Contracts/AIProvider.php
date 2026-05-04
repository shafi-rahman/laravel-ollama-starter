<?php
namespace App\Services\AI\Contracts;

interface AIProvider
{
    public function generate(array $messages, string $model): array;
    public function stream(array $messages, string $model): mixed;
}