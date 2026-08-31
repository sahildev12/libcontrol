<?php

return [
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'product' => [
        'name' => env('LIBSPACE_PRODUCT_NAME', 'LibSpace'),
        'company' => env('LIBSPACE_COMPANY_NAME', 'Phenomit'),
        'company_url' => env('LIBSPACE_COMPANY_URL', 'https://phenomit.com'),
        'byline' => env('LIBSPACE_PRODUCT_BYLINE', 'LibSpace is a product by Phenomit.com'),
    ],

    'brand' => [
        'public_path' => 'brand',
        'default_favicon' => 'brand/favicon.ico',
        'default_simple_logo' => null,
        'default_logo_with_text' => null,
    ],

    'defaults' => [
        'student_code_padding' => 3,
        'expiry_reminder_days' => 10,
        'plan_tier' => 'starter',
    ],

    'plans' => [
        'starter' => [
            'label' => 'Starter',
            'max_seats' => 100,
            'max_halls' => 5,
            'max_branches' => 1,
        ],
        'pro' => [
            'label' => 'Pro',
            'max_seats' => 500,
            'max_halls' => 10,
            'max_branches' => 2,
        ],
        'custom' => [
            'label' => 'Custom',
            'max_seats' => null,
            'max_halls' => null,
            'max_branches' => null,
        ],
    ],

    'license_server' => [
        'enabled' => filter_var(env('LIBSPACE_LICENSE_SERVER', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'deployment' => [
        'license_key' => env('LIBSPACE_LICENSE_KEY'),
        'sync_endpoint' => env('LIBSPACE_SYNC_ENDPOINT'),
        'sync_endpoint_encoded' => env('LIBSPACE_SYNC_ENDPOINT_ENCODED', 'aHR0cHM6Ly9saWJzcGFjZS5waGVub21pdC5jb20vYXBpL3J1bnRpbWUvc3luYw=='),
        'grace_days' => (int) env('LIBSPACE_LICENSE_GRACE_DAYS', 7),
        'sync_interval' => (int) env('LIBSPACE_SYNC_INTERVAL', 3600),
    ],
];
