<?php

namespace App\Services\AI\Exceptions;

use Exception;

class AiProviderException extends Exception
{
    protected $provider;
    protected $rawResponse;

    public function __construct(string $message, string $provider = 'unknown', $rawResponse = null, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->provider = $provider;
        $this->rawResponse = $rawResponse;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }
}
