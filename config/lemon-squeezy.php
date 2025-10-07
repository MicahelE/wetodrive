<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy API Key
    |--------------------------------------------------------------------------
    |
    | The Lemon Squeezy API key is used to authenticate with the Lemon Squeezy
    | API. You can find your API key in the Lemon Squeezy dashboard. You can
    | find your API key in the Lemon Squeezy dashboard under the "API" section.
    |
    */

    'api_key' => env('LEMONSQUEEZY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy Signing Secret
    |--------------------------------------------------------------------------
    |
    | The Lemon Squeezy signing secret is used to verify that the webhook
    | requests are coming from Lemon Squeezy. You can find your signing
    | secret in the Lemon Squeezy dashboard under the "Webhooks" section.
    |
    */

    'signing_secret' => env('LEMONSQUEEZY_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy Url Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI where routes from Lemon Squeezy will be served
    | from. The URL built into Lemon Squeezy is used by default; however,
    | you can modify this path as you see fit for your application.
    |
    */

    'path' => env('LEMON_SQUEEZY_PATH', 'lemon-squeezy'),

    /*
    |--------------------------------------------------------------------------
    | Lemon Squeezy Store
    |--------------------------------------------------------------------------
    |
    | This is the ID of your Lemon Squeezy store. You can find your store
    | ID in the Lemon Squeezy dashboard. The entered value should be the
    | part after the # sign.
    |
    */

    'store' => env('LEMONSQUEEZY_STORE_ID'),
    'store_id' => env('LEMONSQUEEZY_STORE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Default Redirect URL
    |--------------------------------------------------------------------------
    |
    | This is the default redirect URL that will be used when a customer
    | is redirected back to your application after completing a purchase
    | from a checkout session in your Lemon Squeezy store.
    |
    */

    'redirect_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('LEMON_SQUEEZY_CURRENCY_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Product Variant IDs
    |--------------------------------------------------------------------------
    |
    | These are the variant IDs for your subscription plans in LemonSqueezy.
    | You can find these in your LemonSqueezy dashboard after creating your
    | products and variants.
    |
    */

    'variant_ids' => [
        'pro' => env('LEMONSQUEEZY_VARIANT_ID_PRO'),
        'premium' => env('LEMONSQUEEZY_VARIANT_ID_PREMIUM'),
    ],

];
