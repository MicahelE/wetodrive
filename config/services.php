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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'chunk_size' => env('GOOGLE_DRIVE_CHUNK_SIZE', 10485760), // Default 10MB, can be configured via env

        // Google Picker. The key is sent to the browser by design, so it must be
        // referrer-restricted in Cloud Console rather than treated as a secret.
        'picker_key' => env('GOOGLE_PICKER_KEY'),

        // Picker also needs the Cloud project number, and renders a blank dialog
        // with no error if it is missing. It is the numeric prefix of the OAuth
        // client id, so it is derived rather than configured twice.
        'picker_app_id' => env('GOOGLE_PICKER_APP_ID') ?: strtok((string) env('GOOGLE_CLIENT_ID'), '-'),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    ],

];
