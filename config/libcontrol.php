<?php

return [
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),

    'modules' => [
        'enquiries' => filter_var(env('LIBCONTROL_ENQUIRIES_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'growth' => filter_var(env('LIBCONTROL_GROWTH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'product' => [
        'name' => env('LIBCONTROL_PRODUCT_NAME', 'LibControl'),
        'company' => env('LIBCONTROL_COMPANY_NAME', 'Phenomit'),
        'company_url' => env('LIBCONTROL_COMPANY_URL', 'https://phenomit.com'),
        'byline' => env('LIBCONTROL_PRODUCT_BYLINE', 'LibControl is a product by Phenomit.com'),
    ],

    'brand' => [
        'public_path' => 'brand',
        'default_favicon' => 'logo/png-background/light-favicon/favicon-32x32.png',
        'default_favicon_light' => 'logo/png-background/light-favicon/favicon-32x32.png',
        'default_favicon_light_16' => 'logo/png-background/light-favicon/favicon-16x16.png',
        'default_favicon_light_ico' => 'logo/png-background/light-favicon/favicon.ico',
        'default_favicon_light_apple' => 'logo/png-background/light-favicon/apple-touch-icon.png',
        'default_favicon_dark' => 'logo/png-background/dark-favicon/favicon-32x32.png',
        'default_favicon_dark_16' => 'logo/png-background/dark-favicon/favicon-16x16.png',
        'default_favicon_dark_ico' => 'logo/png-background/dark-favicon/favicon.ico',
        'default_favicon_dark_apple' => 'logo/png-background/dark-favicon/apple-touch-icon.png',
        'default_simple_logo' => 'logo/png-background/yellow-lc-logo.png',
        'default_logo_with_text' => 'logo/png-background/lc-logo-landscape.png',
        'dark_icon' => 'logo/png-background/white-icon-only.png',
        'dark_wide' => 'logo/png-background/white-icon-only.png',
        'dark_wide_logo_with_text' => 'logo/png-background/white-lc-logo-landscape.png',
        'light_icon' => 'logo/png-background/yellow-lc-logo.png',
        'light_wide' => 'logo/png-background/lc-logo-landscape.png',
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
        'phone' => env('LIBCONTROL_SUPPORT_PHONE', '8076105181'),
        'whatsapp' => env('LIBCONTROL_SUPPORT_WHATSAPP', '8076105181'),
        'faq_url' => env('LIBCONTROL_SUPPORT_FAQ_URL', 'https://libcontrol.in/support-articles.html'),
        'articles_url' => env('LIBCONTROL_SUPPORT_ARTICLES_URL', 'https://libcontrol.in/support-articles.html'),
        'documentation_url' => env('LIBCONTROL_SUPPORT_DOCUMENTATION_URL', 'https://libcontrol.in/documentation.html'),
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
            explode(',', (string) env('LIBCONTROL_TENANT_LANDLORD_HOSTS', 'libcontrol.in,www.libcontrol.in,localhost,127.0.0.1'))
        ))),
        'landlord_connection' => env('LIBCONTROL_TENANT_LANDLORD_CONNECTION', 'mysql'),
    ],

    /*
    | LibControl product marketing site (libcontrol-website/) on landlord hosts only.
    | Client libraries (e.g. aims.phenomit.com) keep the per-library public website.
    */
    'marketing_site' => [
        'enabled' => filter_var(env('LIBCONTROL_MARKETING_SITE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'path' => env('LIBCONTROL_MARKETING_SITE_PATH', base_path('libcontrol-website')),
        'hosts' => array_values(array_filter(array_map(
            static fn (string $host) => strtolower(trim($host)),
            explode(',', (string) env('LIBCONTROL_MARKETING_SITE_HOSTS', 'libcontrol.in,www.libcontrol.in'))
        ))),
    ],

    /*
    | Public demo install (demo.libcontrol.in): shows these logins on the sign-in page.
    */
    'demo' => [
        'enabled' => filter_var(env('LIBCONTROL_DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN),
        'admin_email' => env('LIBCONTROL_DEMO_ADMIN_EMAIL', 'admin@demo.libcontrol.in'),
        'admin_password' => env('LIBCONTROL_DEMO_ADMIN_PASSWORD', 'Demo@1234'),
        'branch_email' => env('LIBCONTROL_DEMO_BRANCH_EMAIL', 'branch@demo.libcontrol.in'),
        'branch_password' => env('LIBCONTROL_DEMO_BRANCH_PASSWORD', 'Demo@1234'),
    ],

    'id_card_templates' => [
        'classic' => [
            'label' => 'LibControl Card 1',
            'description' => 'Navy header with yellow accent, photo frame, and dotted detail lines.',
        ],
        'modern' => [
            'label' => 'LibControl Card 2',
            'description' => 'Wave header and footer with LibControl branding.',
        ],
        'professional' => [
            'label' => 'LibControl Card 3',
            'description' => 'Curved navy bands top and bottom with centered student fields.',
        ],
    ],

    /*
     * Positions are percentages of the card (width for left/width/radius, height for top/height),
     * measured from the 1012×638 background artwork. Used by the HTML cards and the PNG export.
     */
    'id_card_layouts' => [
        'classic' => [
            'background' => 'logo/id-cards/card-classic.jpeg',
            'photo' => ['left' => 4.923, 'top' => 30.882, 'width' => 27.692, 'height' => 50.98, 'radius' => 2.46],
        ],
        'modern' => [
            'background' => 'logo/id-cards/card-modern.jpeg',
            'photo' => ['left' => 4.84, 'top' => 34.33, 'width' => 21.15, 'height' => 40.28, 'radius' => 1.7],
        ],
        'professional' => [
            'background' => 'logo/id-cards/card-professional.jpeg',
            'photo' => ['left' => 7.71, 'top' => 31.35, 'width' => 26.28, 'height' => 49.84, 'radius' => 0],
        ],
    ],

    /*
     * Student details on the dotted lines (same on every background). `line` is the dotted line's
     * height on the card; text sits just above it, from `left` to `line_end`. Font size is % of card width.
     */
    'id_card_text' => [
        'font_size' => 3.4,
        'line_end' => 92.2,
        'gap_above_line' => 0.9,
        'rows' => [
            'name' => ['left' => 52.2, 'line' => 37.22],
            'father_name' => ['left' => 54.4, 'line' => 50.63],
            'date_of_birth' => ['left' => 65.7, 'line' => 63.95],
            'student_id' => ['left' => 60.3, 'line' => 76.96],
        ],
    ],

    'id_card_preview_sample' => [
        'name' => 'Aarav Sharma',
        'father_name' => 'Rajesh Sharma',
        'date_of_birth' => '15-08-2002',
        'student_id' => 'MLC-901',
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
