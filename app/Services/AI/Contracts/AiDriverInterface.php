<?php

namespace App\Services\AI\Contracts;

interface AiDriverInterface
{
    /**
     * Send a simple prompt to the AI and get a text response.
     */
    public function prompt(string $prompt, array $options = []): ?string;

    /**
     * Send a prompt with a document (base64) and get a structured response.
     * This method is designed for multimodal inputs (Vision/Documents).
     */
    public function extractJson(string $prompt, string $base64, string $mimeType, array $options = []): ?array;

    /**
     * Handle multi-turn conversations.
     * $messages format: [['role' => 'user|assistant', 'content' => '...'], ...]
     */
    public function chat(array $messages, array $options = []): ?string;

    /**
     * Get the underlying model name being used.
     */
    public function getModel(): string;
}
