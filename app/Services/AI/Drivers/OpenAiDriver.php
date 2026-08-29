<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiDriver extends BaseDriver
{
    protected string $baseUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->model = $config['model'] ?? 'gpt-4o';
    }

    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$base64}"
                            ]
                        ]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.1
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($options['timeout'] ?? 120)
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $text = $response->json('choices.0.message.content');
                return json_decode($text, true);
            }

            throw new AiProviderException("OpenAI Extraction Error: " . $response->body(), 'openai', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function chat(array $messages, array $options = []): ?string
    {
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 2048,
        ];

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($options['timeout'] ?? 60)
                ->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            throw new AiProviderException("OpenAI Chat Error: " . $response->body(), 'openai', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
