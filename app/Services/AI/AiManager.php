<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AiDriverInterface;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\OpenAiDriver;
use App\Services\AI\Drivers\ClaudeDriver;
use App\Services\AI\Drivers\LocalAiDriver;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('ai.default', 'gemini');
    }

    /**
     * Create the Gemini driver.
     */
    protected function createGeminiDriver(): AiDriverInterface
    {
        $config = $this->config->get('ai.drivers.gemini');
        $this->validateConfig($config, 'Gemini');

        return new GeminiDriver($config);
    }

    /**
     * Create the OpenAI driver.
     */
    protected function createOpenaiDriver(): AiDriverInterface
    {
        $config = $this->config->get('ai.drivers.openai');
        $this->validateConfig($config, 'OpenAI');

        return new OpenAiDriver($config);
    }

    /**
     * Create the Claude (Anthropic) driver.
     */
    protected function createAnthropicDriver(): AiDriverInterface
    {
        $config = $this->config->get('ai.drivers.anthropic');
        $this->validateConfig($config, 'Anthropic');

        return new ClaudeDriver($config);
    }

    /**
     * Create a local OpenAI-compatible driver.
     */
    protected function createLocalDriver(): AiDriverInterface
    {
        $config = $this->config->get('ai.drivers.local', []);

        return new LocalAiDriver($config);
    }

    /**
     * Helper to validate driver config.
     */
    protected function validateConfig(?array $config, string $driverName): void
    {
        if (empty($config['api_key'])) {
            throw new InvalidArgumentException("{$driverName} API key is not configured.");
        }
    }

    /**
     * Get a specific driver instance.
     * @return AiDriverInterface
     */
    public function driver($driver = null): AiDriverInterface
    {
        return parent::driver($driver);
    }

    /**
     * Send a prompt through the configured provider chain.
     */
    public function prompt(string $prompt, array $options = []): ?string
    {
        $options = $this->withDefaultTimeout($options, 'prompt');

        return $this->attemptWithFallback('prompt', [$prompt, $options], $options);
    }

    /**
     * Send a chat through the configured provider chain.
     */
    public function chat(array $messages, array $options = []): ?string
    {
        $options = $this->withDefaultTimeout($options, 'chat');

        return $this->attemptWithFallback('chat', [$messages, $options], $options);
    }

    /**
     * Extract JSON through the configured provider chain.
     */
    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array
    {
        $options = $this->withDefaultTimeout($options, 'extract_json');

        return $this->attemptWithFallback('extractJson', [$prompt, $base64, $mimeType, $options], $options);
    }

    protected function attemptWithFallback(string $method, array $arguments, array $options)
    {
        $drivers = $this->driverFallbackOrder($options);
        $lastException = null;

        foreach ($drivers as $driverName) {
            try {
                $result = $this->driver($driverName)->{$method}(...$arguments);

                if ($this->hasUsableResult($result)) {
                    if ($driverName !== $this->getDefaultDriver()) {
                        Log::info('AI fallback provider succeeded.', [
                            'method' => $method,
                            'driver' => $driverName,
                        ]);
                    }

                    return $result;
                }

                Log::warning('AI provider returned an empty response; trying fallback.', [
                    'method' => $method,
                    'driver' => $driverName,
                ]);
            } catch (Throwable $e) {
                $lastException = $e;

                Log::warning('AI provider failed; trying fallback.', [
                    'method' => $method,
                    'driver' => $driverName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return null;
    }

    protected function driverFallbackOrder(array $options): array
    {
        $primary = $options['driver'] ?? $this->getDefaultDriver();

        if (($options['fallback'] ?? true) === false || !$this->config->get('ai.fallback.enabled', true)) {
            return [$primary];
        }

        $fallbackDrivers = $options['fallback_drivers'] ?? $this->config->get('ai.fallback.order', []);

        return collect(array_merge([$primary], $fallbackDrivers))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function hasUsableResult($result): bool
    {
        if (is_string($result)) {
            return trim($result) !== '';
        }

        if (is_array($result)) {
            return !empty($result);
        }

        return $result !== null;
    }

    protected function withDefaultTimeout(array $options, string $operation): array
    {
        if (!isset($options['timeout'])) {
            $timeout = $this->config->get("ai.fallback.timeouts.{$operation}");
            if ($timeout) {
                $options['timeout'] = (int) $timeout;
            }
        }

        return $options;
    }
}
