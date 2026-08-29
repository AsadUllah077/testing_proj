<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Services\AI\Contracts\AiDriverInterface driver(string $driver = null)
 * @method static string|null prompt(string $prompt, array $options = [])
 * @method static string|null chat(array $messages, array $options = [])
 * @method static array|null extractJson(string $prompt, string $base64, string $mimeType, array $options = [])
 * 
 * @see \App\Services\AI\AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai.manager';
    }
}
