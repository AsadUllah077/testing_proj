<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Exceptions\AiProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalAiDriver extends BaseDriver
{
    protected string $baseUrl;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->model = $config['model'] ?? 'qwen-sql';
        $this->baseUrl = $config['base_url'] ?? 'http://127.0.0.1:8000/v1/chat/completions';
    }

    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array
    {
        throw new AiProviderException(
            'Local AI driver does not support document/image extraction.',
            'local',
            ['model' => $this->model]
        );
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
            $request = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout($options['timeout'] ?? 60);

            if (!empty($this->apiKey)) {
                $request = $request->withToken($this->apiKey);
            }

            $response = $request->post($this->baseUrl, $payload);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            throw new AiProviderException("Local AI Chat Error: " . $response->body(), 'local', $response->json());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
