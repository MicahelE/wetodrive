<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Polar Access Token
    |--------------------------------------------------------------------------
    |
    | Your Polar organization access token, used to authenticate API requests.
    |
    */

    'api_key' => env('POLAR_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Polar Organization ID
    |--------------------------------------------------------------------------
    */

    'organization_id' => env('POLAR_ORGANIZATION_ID'),

    /*
    |--------------------------------------------------------------------------
    | Polar Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Used to verify incoming webhook payloads from Polar.
    |
    */

    'webhook_secret' => env('POLAR_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Polar Environment
    |--------------------------------------------------------------------------
    |
    | Set to "sandbox" for testing or "production" for live.
    |
    */

    'environment' => env('POLAR_ENVIRONMENT', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Product IDs
    |--------------------------------------------------------------------------
    |
    | Map subscription plan slugs to their Polar product UUIDs. Create the
    | products in the Polar dashboard and paste the IDs here.
    |
    */

    'product_ids' => [
        'pro' => env('POLAR_PRODUCT_ID_PRO'),
        'premium' => env('POLAR_PRODUCT_ID_PREMIUM'),
    ],

];
