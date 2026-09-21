<?php

return [
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'modules' => [
        'enquiries' => filter_var(env('LIBCONTROL_ENQUIRIES_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'product' => [
        'name' => env('LIBCONTROL_PRODUCT_NAME', 'LibControl'),
        'company' => env('LIBCONTROL_COMPANY_NAME', 'Phenomit'),
        'company_url' => env('LIBCONTROL_COMPANY_URL', 'https://phenomit.com'),
        'byline' => env('LIBCONTROL_PRODUCT_BYLINE', 'LibControl is a product by Phenomit.com'),
    ],

    'brand' => [
        'public_path' => 'brand',
        'default_favicon' => 'logo/png-background/yellow-lc-logo.png',
        'default_simple_logo' => 'logo/png-background/yellow-lc-logo.png',
        'default_logo_with_text' => 'logo/png-background/yellow-lc-logo.png',
        'dark_icon' => 'logo/png-background/yellow-lc-logo.png',
        'dark_wide' => 'logo/png-background/yellow-lc-logo.png',
        'light_icon' => 'logo/png-background/yellow-lc-logo.png',
        'light_wide' => 'logo/png-background/yellow-lc-logo.png',
    ],

    'defaults' => [
        'student_code_padding' => 3,
        'expiry_reminder_days' => 10,
        'plan_tier' => 'starter',
        'admin_impact_text' => 'Total 124+ Libraries Registered',
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
        'enabled' => filter_var(env('LIBCONTROL_LICENSE_SERVER', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'support' => [
        'email' => env('LIBCONTROL_SUPPORT_EMAIL', 'support@phenomit.com'),
        'phone' => env('LIBCONTROL_SUPPORT_PHONE', ''),
        'whatsapp' => env('LIBCONTROL_SUPPORT_WHATSAPP', '8901223423'),
        'faq_url' => env('LIBCONTROL_SUPPORT_FAQ_URL', 'https://phenomit.com/libcontrol/support-articles.html'),
        'articles_url' => env('LIBCONTROL_SUPPORT_ARTICLES_URL', 'https://phenomit.com/libcontrol/support-articles.html'),
        'documentation_url' => env('LIBCONTROL_SUPPORT_DOCUMENTATION_URL', 'https://phenomit.com/libcontrol/documentation.html'),
        'whatsapp_button_image' => env(
            'LIBCONTROL_SUPPORT_WHATSAPP_IMAGE',
            'https://renprints.com/p_assets/img/footer/renprints-whatsapp-us-.png',
        ),
    ],

    'deployment' => [
        'license_key' => env('LIBCONTROL_LICENSE_KEY'),
        // URL sent to Phenomit for the student app (must be reachable from phones, not 127.0.0.1).
        'public_url' => env('LIBCONTROL_PUBLIC_URL'),
        'sync_endpoint' => env('LIBCONTROL_SYNC_ENDPOINT'),
        'support_endpoint' => env('LIBCONTROL_SUPPORT_ENDPOINT'),
        'sync_endpoint_encoded' => env('LIBCONTROL_SYNC_ENDPOINT_ENCODED', 'aHR0cHM6Ly9saWJjb250cm9sLnBoZW5vbWl0LmNvbS9hcGkvcnVudGltZS9zeW5j'),
        'grace_days' => (int) env('LIBCONTROL_LICENSE_GRACE_DAYS', 7),
        // 0 = ping on every eligible page load. Higher values throttle background syncs.
        'sync_interval' => (int) env('LIBCONTROL_SYNC_INTERVAL', 3600),
    ],

    'discovery' => [
        'secret' => env('LIBCONTROL_DISCOVERY_SECRET', 'libcontrol-discovery-v1-phenomit-8f3c2a9e1b4d7e6f5a0c8b2d1e9f4a7'),
    ],

    'tenancy' => [
        'enabled' => filter_var(env('LIBCONTROL_TENANCY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'base_domain' => env('LIBCONTROL_TENANT_BASE_DOMAIN', 'phenomit.com'),
        'landlord_hosts' => array_values(array_filter(array_map(
            static fn (string $host) => strtolower(trim($host)),
            explode(',', (string) env('LIBCONTROL_TENANT_LANDLORD_HOSTS', 'libcontrol.phenomit.com,localhost,127.0.0.1'))
        ))),
        'landlord_connection' => env('LIBCONTROL_TENANT_LANDLORD_CONNECTION', 'mysql'),
    ],

    'id_card_templates' => [
        'classic' => [
            'label' => 'Classic Sidebar',
            'description' => 'Purple sidebar with photo, student details, and barcode.',
        ],
        'modern' => [
            'label' => 'Modern Header',
            'description' => 'Gradient header, photo, details, and QR code.',
        ],
        'professional' => [
            'label' => 'Professional',
            'description' => 'Clean corporate layout with wave accent and validity strip.',
        ],
    ],

    'id_card_preview_sample' => [
        'name' => 'Aarav Sharma',
        'student_id' => 'STU00123',
        'course' => 'BCA',
        'branch' => 'Main Branch',
        'valid_till' => '31 Dec 2026',
        'initials' => 'AS',
    ],

    'expense_categories' => [
        'rent' => 'Rent & Lease',
        'utilities' => 'Utilities',
        'salaries' => 'Salaries & Wages',
        'maintenance' => 'Maintenance & Repairs',
        'supplies' => 'Supplies & Stationery',
        'marketing' => 'Marketing & Advertising',
        'equipment' => 'Equipment & Furniture',
        'internet' => 'Internet & Software',
        'taxes' => 'Taxes & Licenses',
        'other' => 'Other',
    ],

    'install' => [
        'token' => env('LIBCONTROL_SETUP_TOKEN'),
        'product_name' => env('LIBCONTROL_PRODUCT_NAME'),
        'display_name' => env('LIBCONTROL_CLIENT_NAME'),
        'student_code_prefix' => env('LIBCONTROL_INSTALL_STUDENT_CODE_PREFIX'),
        'developer_email' => env('LIBCONTROL_DEVELOPER_EMAIL'),
        'developer_password' => env('LIBCONTROL_DEVELOPER_PASSWORD'),
        'admin_email' => env('LIBCONTROL_ADMIN_EMAIL'),
        'admin_password' => env('LIBCONTROL_ADMIN_PASSWORD'),
        'admin_name' => env('LIBCONTROL_ADMIN_NAME', 'Admin'),
    ],
];
