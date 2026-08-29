<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI driver that will be used by the
    | AiManager when no driver is explicitly specified.
    |
    */

    'default' => env('AI_DRIVER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | AI Fallback
    |--------------------------------------------------------------------------
    |
    | When enabled, the AI manager will try the default driver first and then
    | move through this order when a provider times out, errors, or returns an
    | empty response. Keep cheaper/local providers first if cost matters.
    |
    */

    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
        'timeouts' => [
            'prompt' => env('AI_FALLBACK_PROMPT_TIMEOUT', 25),
            'chat' => env('AI_FALLBACK_CHAT_TIMEOUT', 25),
            'extract_json' => env('AI_FALLBACK_EXTRACT_TIMEOUT', 90),
        ],
        'order' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('AI_FALLBACK_ORDER', 'gemini,openai,anthropic,local'))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Drivers Configuration
    |--------------------------------------------------------------------------
    */

    'drivers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model'   => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o'),
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20240620'),
        ],

        'local' => [
            'api_key'  => env('LOCAL_AI_API_KEY'),
            'base_url' => env('LOCAL_AI_BASE_URL', 'http://192.168.1.141:8000/v1/chat/completions'),
            'model'    => env('LOCAL_AI_MODEL', 'qwen-sql'),
        ],
    ],

];
