<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EduBook platform configuration
    |--------------------------------------------------------------------------
    |
    | These values mirror the administrator-configurable settings stored in the
    | `settings` table. The values here are the defaults used when an admin
    | has not overridden them.
    |
    */

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', ''),
        'key_secret' => env('RAZORPAY_KEY_SECRET', ''),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', ''),
        'mode' => env('RAZORPAY_MODE', 'sandbox'),
    ],

    'meeting' => [
        'provider' => env('MEETING_PROVIDER', 'manual'),
        'api_key' => env('MEETING_API_KEY', ''),
        'api_secret' => env('MEETING_API_SECRET', ''),
    ],

    'support' => [
        'phone' => env('EDUBOOK_SUPPORT_PHONE', '18001234567'),
        'email' => env('EDUBOOK_SUPPORT_EMAIL', 'support@edubook.test'),
    ],

    'commission' => [
        'percent' => env('EDUBOOK_COMMISSION_PERCENT', 15),
    ],
];
