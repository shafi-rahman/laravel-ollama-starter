<?php

private function compileHistory(array $history): string
{
    $text = '';

    foreach ($history as $msg) {
        $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
        $text .= "{$role}: {$msg['content']}\n";
    }

    return $text;
}