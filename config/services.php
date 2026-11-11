<?php

return [
    'meeting' => [
        'provider' => env('MEETING_PROVIDER', 'manual'),
    ],
    'google_apps_script' => [
        'web_app_url' => env('GOOGLE_APPS_SCRIPT_WEB_APP_URL'),
        'shared_secret' => env('GOOGLE_APPS_SCRIPT_SHARED_SECRET'),
    ],
    'appointments' => [
        'selection_hold_minutes' => (int) env('APPOINTMENT_SELECTION_HOLD_MINUTES', 10),
    ],
    'safepay' => [
        'environment' => env('SAFEPAY_ENV', 'sandbox'),
        'production_enabled' => (bool) env('SAFEPAY_PRODUCTION_ENABLED', false),
        'api_key' => env('SAFEPAY_API_KEY'),
        'secret_key' => env('SAFEPAY_SECRET_KEY'),
        'webhook_secret' => env('SAFEPAY_WEBHOOK_SECRET'),
        'intent' => env('SAFEPAY_INTENT', 'CYBERSOURCE'),
        'hold_minutes' => (int) env('SAFEPAY_HOLD_MINUTES', 55),
        'api_host' => env('SAFEPAY_ENV', 'sandbox') === 'production'
            ? 'https://api.getsafepay.com'
            : 'https://sandbox.api.getsafepay.com',
        'checkout_host' => env('SAFEPAY_ENV', 'sandbox') === 'production'
            ? 'https://getsafepay.com'
            : 'https://sandbox.api.getsafepay.com',
    ],
];
