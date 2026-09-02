<?php

return [
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'modules' => [
        'enquiries' => filter_var(env('LIBSPACE_ENQUIRIES_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

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
        // 0 = ping on every eligible page load. Higher values throttle background syncs.
        'sync_interval' => (int) env('LIBSPACE_SYNC_INTERVAL', 3600),
    ],

    'discovery' => [
        'secret' => env('LIBSPACE_DISCOVERY_SECRET', 'libspace-discovery-v1-phenomit-8f3c2a9e1b4d7e6f5a0c8b2d1e9f4a7'),
    ],

    'tenancy' => [
        'enabled' => filter_var(env('LIBSPACE_TENANCY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'base_domain' => env('LIBSPACE_TENANT_BASE_DOMAIN', 'phenomit.com'),
        'landlord_hosts' => array_values(array_filter(array_map(
            static fn (string $host) => strtolower(trim($host)),
            explode(',', (string) env('LIBSPACE_TENANT_LANDLORD_HOSTS', 'libspace.phenomit.com,localhost,127.0.0.1'))
        ))),
        'landlord_connection' => env('LIBSPACE_TENANT_LANDLORD_CONNECTION', 'mysql'),
    ],

    'install' => [
        'token' => env('LIBSPACE_SETUP_TOKEN'),
        'product_name' => env('LIBSPACE_PRODUCT_NAME'),
        'developer_email' => env('LIBSPACE_DEVELOPER_EMAIL'),
        'developer_password' => env('LIBSPACE_DEVELOPER_PASSWORD'),
        'admin_email' => env('LIBSPACE_ADMIN_EMAIL'),
        'admin_password' => env('LIBSPACE_ADMIN_PASSWORD'),
        'admin_name' => env('LIBSPACE_ADMIN_NAME', 'Admin'),
    ],
];
