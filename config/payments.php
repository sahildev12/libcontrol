<?php

return [
    /*
    | PhonePe Standard Checkout v2. Credentials live only on the Katsindia hub (libcontrol.in);
    | client installs pay through the hub and never see these keys.
    */
    'phonepe' => [
        'env' => env('PHONEPE_ENV', 'sandbox'), // sandbox | production
        'client_id' => env('PHONEPE_CLIENT_ID'),
        'client_version' => env('PHONEPE_CLIENT_VERSION', '1'),
        'client_secret' => env('PHONEPE_CLIENT_SECRET'),
        'webhook_username' => env('PHONEPE_WEBHOOK_USERNAME'),
        'webhook_password' => env('PHONEPE_WEBHOOK_PASSWORD'),
        'order_expire_seconds' => (int) env('PHONEPE_ORDER_EXPIRE_SECONDS', 900),
        'launch_url_ttl_minutes' => (int) env('PHONEPE_LAUNCH_URL_TTL_MINUTES', 30),
        'timeout' => 20,
        'endpoints' => [
            'sandbox' => [
                'auth' => 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token',
                'base' => 'https://api-preprod.phonepe.com/apis/pg-sandbox',
            ],
            'production' => [
                'auth' => 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token',
                'base' => 'https://api.phonepe.com/apis/pg',
            ],
        ],
    ],

    /*
    | LibControl software subscription prices in paise (GST inclusive as shown on libcontrol.in).
    */
    'software_plans' => [
        'starter' => [
            'label' => 'Starter',
            'monthly' => 49900,
            'yearly' => 499000,
        ],
        'pro' => [
            'label' => 'Pro',
            'monthly' => 99900,
            'yearly' => 999000,
        ],
    ],

    'plan_rank' => [
        'starter' => 1,
        'pro' => 2,
        'custom' => 3,
    ],
];
