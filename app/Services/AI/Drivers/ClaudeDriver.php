<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeDriver extends BaseDriver
{
    protected string $baseUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->model = $config['model'] ?? 'claude-3-5-sonnet-20240620';
    }

    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt . "\nReturn ONLY valid JSON.",
                        ],
                    ],
                ]
            ],
            'temperature' => 0.1
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout($options['timeout'] ?? 120)->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $text = $response->json('content.0.text');
                return $this->parseJson($text);
            }

            throw new AiProviderException("Claude Extraction Error: " . $response->body(), 'anthropic', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function chat(array $messages, array $options = []): ?string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 2048,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout($options['timeout'] ?? 60)->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json('content.0.text');
            }

            throw new AiProviderException("Claude Chat Error: " . $response->body(), 'anthropic', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
