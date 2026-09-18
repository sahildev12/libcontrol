<?php

/**
 * Internal per-deployment customization manifest (not shown in admin UI).
 *
 * Cursor/agents update this file when shipping client-specific changes.
 * Copy and edit for each client LibControl installation so merges stay safe.
 */
return [
    'profile' => [
        'client_name' => env('LIBCONTROL_CLIENT_NAME', 'LibSpace Demo'),
        'deployment_slug' => env('LIBCONTROL_DEPLOYMENT_SLUG', 'libspace'),
        'library_style' => env('LIBCONTROL_LIBRARY_STYLE', 'standard'),
        'baseline_version' => env('LIBCONTROL_BASELINE_VERSION', '2.1.3'),
        'maintainer' => env('LIBCONTROL_CLIENT_MAINTAINER', 'Phenomit'),
        'summary' => 'Primary development copy for LibControl product work and client demos.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Library styles (product line presets)
    |--------------------------------------------------------------------------
    | standard  — full LibControl feature set
    | finance   — stronger finance / reporting emphasis
    | mobile    — student mobile app first
    | custom    — bespoke client build
    */
    'library_styles' => [
        'standard' => 'Standard library operations',
        'finance' => 'Finance-focused reporting',
        'mobile' => 'Mobile student app emphasis',
        'custom' => 'Fully bespoke client build',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature map vs baseline product
    |--------------------------------------------------------------------------
    | status: baseline | enabled | disabled | custom | pending
    */
    'features' => [
        'student_mobile_app' => [
            'label' => 'Student mobile app',
            'status' => 'custom',
            'notes' => 'Library code connect, PIN, biometric unlock, remembered student.',
        ],
        'finance_module' => [
            'label' => 'Finance module',
            'status' => 'custom',
            'notes' => 'Renamed from Profit-Loss. Overview, Statement, Expenses tabs.',
        ],
        'finance_statement_filters' => [
            'label' => 'Finance statement filters',
            'status' => 'custom',
            'notes' => 'Search, type, and payment filters on Statement tab.',
        ],
        'fee_setup_payment' => [
            'label' => 'Receive payment during fee setup',
            'status' => 'custom',
            'notes' => 'Optional payment capture when setting up a student fee plan.',
        ],
        'id_card_templates' => [
            'label' => 'Student ID card templates',
            'status' => 'custom',
            'notes' => 'Dedicated Settings tab with 4 print templates and library logo upload.',
        ],
        'platform_branding_settings' => [
            'label' => 'Client-editable platform branding',
            'status' => 'disabled',
            'notes' => 'Removed from Settings. Clients use LibControl branding like Zoho.',
        ],
        'branch_delete_guards' => [
            'label' => 'Branch delete validation',
            'status' => 'custom',
            'notes' => 'Toast error when branch still has halls/seats or students.',
        ],
        'student_contact_rules' => [
            'label' => 'Mandatory student phone/email',
            'status' => 'custom',
            'notes' => 'Branch setting requires phone OR email (not both always).',
        ],
        'enquiries_module' => [
            'label' => 'Enquiries module',
            'status' => 'disabled',
            'notes' => 'Controlled by LIBCONTROL_ENQUIRIES_ENABLED.',
        ],
        'attendance_addon' => [
            'label' => 'Attendance addon',
            'status' => 'baseline',
            'notes' => 'Optional addon package.',
        ],
        'onboarding_tooltips' => [
            'label' => 'New-user tooltips',
            'status' => 'pending',
            'notes' => 'Client checklist item.',
        ],
        'help_support_tab' => [
            'label' => 'Help / Support tab',
            'status' => 'custom',
            'notes' => 'Help & Support nav with ticket creation synced to libcontrol.phenomit.com.',
        ],
        'client_public_website' => [
            'label' => 'Client public website',
            'status' => 'custom',
            'notes' => 'Settings Website tab + /library public page with amenities and social links.',
        ],
        'email_notifications' => [
            'label' => 'Email notifications',
            'status' => 'custom',
            'notes' => 'Welcome, birthday, offers, marketing, recovery emails when student has email.',
        ],
        'student_contact_mandatory' => [
            'label' => 'Mandatory student phone and email',
            'status' => 'custom',
            'notes' => 'Both phone and email required on student registration (family link exempt).',
        ],
        'fee_insight_cards' => [
            'label' => 'Fee management insight cards',
            'status' => 'custom',
            'notes' => 'Received, pending, overdue, outstanding, and expiring-soon cards on Fee Management.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Documented code / UX changes for this deployment
    |--------------------------------------------------------------------------
    */
    'changes' => [
        [
            'area' => 'Finance',
            'summary' => 'Profit-Loss renamed to Finance with ledger statement',
            'detail' => 'Sidebar label, tabs, fee income + expenses combined view.',
            'since' => '2026-09',
        ],
        [
            'area' => 'Settings',
            'summary' => 'ID Cards moved to dedicated tab',
            'detail' => 'Template picker and logo upload separated from General settings.',
            'since' => '2026-09',
        ],
        [
            'area' => 'Mobile',
            'summary' => 'Single APK with library code resolver',
            'detail' => 'Connect via 6-digit code through libcontrol.phenomit.com.',
            'since' => '2026-09',
        ],
        [
            'area' => 'Licensing',
            'summary' => 'Local dev skips license enforcement',
            'detail' => 'APP_ENV=local bypasses deployment license middleware.',
            'since' => '2026-09',
        ],
    ],
];
