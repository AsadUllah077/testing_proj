<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiDriver extends BaseDriver
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->model = $config['model'] ?? 'gemini-2.5-flash';
    }

    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array
    {
        $url = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $genConfig = $this->prepareGenerationConfig($options, true);
            
            $response = Http::timeout($options['timeout'] ?? 120)
                ->retry(3, 2000, function ($exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response?->status() === 503;
                    }
                    return false;
                })
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]],
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => $genConfig
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                return $this->parseJson($text);
            }

            throw new AiProviderException("Gemini Extraction Error: " . $response->body(), 'gemini', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function chat(array $messages, array $options = []): ?string
    {
        $url = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

        $contents = collect($messages)->map(function ($msg) {
            return [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]]
            ];
        })->toArray();

        try {
            // Check if JSON mode is requested via options
            $forceJson = (isset($options['generationConfig']['responseMimeType']) || isset($options['generationConfig']['response_mime_type']));
            $genConfig = $this->prepareGenerationConfig($options, $forceJson);

            $response = Http::timeout($options['timeout'] ?? 60)
                ->retry(3, 2000, function ($exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response?->status() === 503;
                    }
                    return false;
                })
                ->post($url, [
                    'contents' => $contents,
                    'generationConfig' => array_merge([
                        'temperature' => $options['temperature'] ?? 0.3,
                        'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                    ], $genConfig)
                ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text');
            }

            throw new AiProviderException("Gemini Chat Error: " . $response->body(), 'gemini', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    public function getModel(): string
    {
        return $this->model;
    }

    protected function prepareGenerationConfig(array $options, bool $jsonMode = false): array
    {
        $genConfig = $options['generationConfig'] ?? [];
        $isV1 = str_contains($this->baseUrl, '/v1/');
        
        // Add JSON mode if requested
        if ($jsonMode) {
            if ($isV1) {
                $genConfig['response_mime_type'] = 'application/json';
            } else {
                $genConfig['responseMimeType'] = 'application/json';
            }
        }

        // Normalize keys for the other version
        if ($isV1) {
            if (isset($genConfig['responseMimeType'])) {
                $genConfig['response_mime_type'] = $genConfig['responseMimeType'];
                unset($genConfig['responseMimeType']);
            }
        } else {
            if (isset($genConfig['response_mime_type'])) {
                $genConfig['responseMimeType'] = $genConfig['response_mime_type'];
                unset($genConfig['response_mime_type']);
            }
        }

        return $genConfig;
    }
}
