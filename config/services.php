<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'azure_b2c' => [
        'tenant' => env('AZURE_B2C_TENANT_NAME', env('AZURE_B2C_TENANT', env('AZURE_B2C_TENANT_ID'))),
        'tenant_id' => env('AZURE_B2C_TENANT_ID'),
        'domain' => env('AZURE_B2C_DOMAIN'),
        'policy' => env('AZURE_B2C_POLICY_NAME', env('AZURE_B2C_POLICY')),
        'client_id' => env('AZURE_B2C_CLIENT_ID'),
        'client_secret' => env('AZURE_B2C_CLIENT_SECRET'),
        'redirect_uri' => env('AZURE_B2C_REDIRECT_URI'),
        'scope' => env('AZURE_B2C_SCOPE', 'openid profile email'),
    ],



    'performance' => [
        'api_key' => env('PERFORMANCE_API_KEY'),
    ],

];
