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
];
