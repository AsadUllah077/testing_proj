<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Log;

abstract class BaseDriver implements AiDriverInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? '';
    }

    /**
     * Common JSON parsing logic to strip markdown fences.
     */
    protected function parseJson($text): ?array
    {
        if (is_array($text)) return $text;
        if (!is_string($text)) return null;

        $text = preg_replace('/```json\s*/', '', $text);
        $text = preg_replace('/```\s*/', '', $text);
        return json_decode(trim($text), true);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Default prompt implementation using chat.
     */
    public function prompt(string $prompt, array $options = []): ?string
    {
        return $this->chat([['role' => 'user', 'content' => $prompt]], $options);
    }
}
