<?php

return [
    'enabled' => filter_var(env('LIBCONTROL_GROWTH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    | Show the ₹1 payment_test growth package (hub only; demo.libcontrol.in is detected by host).
    */
    'payment_test_visible' => filter_var(env('LIBCONTROL_GROWTH_PAYMENT_TEST', false), FILTER_VALIDATE_BOOLEAN),
    'payment_test_hosts' => array_values(array_filter(array_map(
        static fn (string $host) => strtolower(trim($host)),
        explode(',', (string) env('LIBCONTROL_GROWTH_PAYMENT_TEST_HOSTS', 'libcontrol.in,www.libcontrol.in'))
    ))),

    'review_target' => (int) env('LIBCONTROL_GROWTH_REVIEW_TARGET', 50),

    /*
    |--------------------------------------------------------------------------
    | Growth Score mix (Action Progress × 40% + Business Performance × 60%)
    |--------------------------------------------------------------------------
    */
    'growth_score' => [
        'action_weight' => 0.40,
        'business_weight' => 0.60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Performance factor weights (must total 1.0)
    |--------------------------------------------------------------------------
    */
    'business_performance' => [
        'weights' => [
            'profile_completion' => 0.10,
            'occupancy' => 0.20,
            'renewals' => 0.15,
            'branch_staff' => 0.10,
            'membership_payment' => 0.15,
            'expenses_finance' => 0.10,
            'operations' => 0.15,
            'system_usage' => 0.05,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recommendations (Action Progress guidance)
    |--------------------------------------------------------------------------
    | Free libraries see the easiest `free_limit` open items; an active growth
    | package unlocks all of them plus "Hire an expert". Scoring is never locked.
    */
    'recommendations' => [
        'free_limit' => 3,
        // How many tips the dashboard AI banner shows when a growth package is active.
        'pro_banner_limit' => 8,
        'effort_order' => ['easy' => 0, 'medium' => 1, 'hard' => 2],
        /*
        | Pro-only tips (active growth package). Status comes from Business Performance
        | factors — completing the DIY work raises Growth Score via the 60% BP mix.
        | `min_score` = factor score (0–100) at/above which the tip is considered done.
        | `gain` is a display hint for “+N pts”, not a second scoring ledger.
        */
        'pro_items' => [
            'complete_profile' => [
                'factor' => 'profile_completion',
                'min_score' => 80,
                'effort' => 'easy',
                'gain' => 10,
                'title' => 'Complete your library profile',
                'why' => 'A fuller profile raises Business Performance and builds trust with students.',
                'diy_steps' => [
                    'Open Profile completion from the top bar or Settings.',
                    'Add logo, address, hours, and contact details.',
                    'Save — your Growth Score updates automatically.',
                ],
                'cta_label' => 'Complete profile',
                'cta_route' => 'profile-completion.index',
                'cta_route_fallback' => 'settings.index',
                'expert_service' => null,
            ],
            'fill_seats' => [
                'factor' => 'occupancy',
                'min_score' => 70,
                'effort' => 'medium',
                'gain' => 15,
                'title' => 'Fill more empty seats',
                'why' => 'Occupancy is the biggest Business Performance factor — empty seats drag your Growth Score down.',
                'diy_steps' => [
                    'Open Seats and find vacant seats on the map.',
                    'Assign waiting students or convert trials to paid plans.',
                    'Run a promotion or WhatsApp offer to fill the rest.',
                ],
                'cta_label' => 'Open Seats',
                'cta_route' => 'seats.index',
                'expert_service' => 'whatsapp_marketing',
            ],
            'renew_plans' => [
                'factor' => 'renewals',
                'min_score' => 85,
                'effort' => 'medium',
                'gain' => 12,
                'title' => 'Follow up expiring memberships',
                'why' => 'Renewals keep revenue and retention score high before seats go empty.',
                'diy_steps' => [
                    'Open Fee Management and filter plans ending soon.',
                    'Message those students with a renewal offer.',
                    'Record the renewal payment when they continue.',
                ],
                'cta_label' => 'Open Fee Management',
                'cta_route' => 'fees.index',
                'expert_service' => 'whatsapp_marketing',
            ],
            'collect_fees' => [
                'factor' => 'membership_payment',
                'min_score' => 80,
                'effort' => 'medium',
                'gain' => 12,
                'title' => 'Collect overdue fees',
                'why' => 'Payment collection directly lifts Membership & Payment on your Growth Score.',
                'diy_steps' => [
                    'Open Fee Management and check overdue / partial plans.',
                    'Collect dues and record each payment in LibControl.',
                    'Follow up on remaining overdue accounts this week.',
                ],
                'cta_label' => 'Open Fee Management',
                'cta_route' => 'fees.index',
                'expert_service' => null,
            ],
            'log_expenses' => [
                'factor' => 'expenses_finance',
                'min_score' => 50,
                'effort' => 'easy',
                'gain' => 8,
                'title' => 'Log this month’s expenses',
                'why' => 'Finance tracking unlocks Expenses & Finance scoring — missing data leaves points on the table.',
                'diy_steps' => [
                    'Open Finance / Expenses.',
                    'Add rent, utilities, and other costs for this month.',
                    'Confirm fee income is also being recorded.',
                ],
                'cta_label' => 'Open Finance',
                'cta_route' => 'finance.index',
                'expert_service' => null,
            ],
            'branch_setup' => [
                'factor' => 'branch_staff',
                'min_score' => 80,
                'effort' => 'easy',
                'gain' => 8,
                'title' => 'Finish branch & staff setup',
                'why' => 'Phone, hours, and an active operator improve Branch & Staff score.',
                'diy_steps' => [
                    'Open Branch and add phone / address if missing.',
                    'Set opening hours (or mark 24 hours).',
                    'Ensure a branch staff or admin account is logging in regularly.',
                ],
                'cta_label' => 'Open Branch',
                'cta_route' => 'branch.index',
                'expert_service' => null,
            ],
            'daily_usage' => [
                'factor' => 'system_usage',
                'min_score' => 75,
                'effort' => 'medium',
                'gain' => 8,
                'title' => 'Use LibControl every week',
                'why' => 'Recent fees, seats, students, or enquiry activity raises System Usage score.',
                'diy_steps' => [
                    'Record at least one fee payment, seat assign, or student update this week.',
                    'Keep student phone numbers filled for most members.',
                    'Avoid leaving the desk on paper — log work in LibControl.',
                ],
                'cta_label' => 'Open Dashboard',
                'cta_route' => 'dashboard',
                'expert_service' => null,
            ],
            'clear_enquiries' => [
                'check' => 'stuck_enquiries',
                'effort' => 'medium',
                'gain' => 10,
                'title' => 'Clear stuck enquiries',
                'why' => 'Old “new” enquiries hurt System Usage and lose admissions.',
                'diy_steps' => [
                    'Open Enquiries and sort by oldest new leads.',
                    'Call or WhatsApp each lead within 2 days.',
                    'Mark converted, lost, or follow-up so nothing stays stuck.',
                ],
                'cta_label' => 'Open Enquiries',
                'cta_route' => 'enquiries.index',
                'expert_service' => 'lead_generation',
            ],
        ],
        'items' => [
            'instagram' => [
                'effort' => 'easy',
                'title' => 'Add your Instagram page',
                'why' => 'Students check Instagram before visiting a library.',
                'diy_steps' => [
                    'Open your library Instagram profile and copy its URL.',
                    'Paste it in Settings → Website → Social links.',
                    'Save to refresh your score.',
                ],
                'cta_label' => 'Open Website settings',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'social_marketing',
            ],
            'facebook' => [
                'effort' => 'easy',
                'title' => 'Add your Facebook page',
                'why' => 'A Facebook page builds trust with parents and local students.',
                'diy_steps' => [
                    'Create or open your library Facebook page.',
                    'Paste the page URL in Settings → Website.',
                    'Save to refresh your score.',
                ],
                'cta_label' => 'Open Website settings',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'social_marketing',
            ],
            'website' => [
                'effort' => 'easy',
                'title' => 'Publish your library website',
                'why' => 'A live website with WhatsApp brings direct enquiries.',
                'diy_steps' => [
                    'Go to Settings → Website and turn the website on.',
                    'Add a tagline and your WhatsApp number.',
                    'Save and share the link with students.',
                ],
                'cta_label' => 'Setup Website',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'website_landing',
            ],
            'gbp' => [
                'effort' => 'easy',
                'title' => 'Complete your Google Business Profile',
                'why' => 'Most students find libraries through Google Maps.',
                'diy_steps' => [
                    'Claim your library on Google Business Profile.',
                    'Add photos, the right category, and opening hours.',
                    'Paste your Google Maps listing link in Settings → Website.',
                ],
                'cta_label' => 'Open Website settings',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'gbp_boost',
            ],
            'referral' => [
                'effort' => 'easy',
                'title' => 'Start a referral offer',
                'why' => 'Happy members bring friends when there is a reward.',
                'diy_steps' => [
                    'Open Grow My Library → Referral campaign and write an offer, e.g. "Refer a friend, get ₹200 off".',
                    'Activate it and send the share message to your students.',
                    'Friends enter the student’s ID when they enquire or join — give the reward once they join.',
                ],
                'cta_label' => 'Open Students',
                'cta_route' => 'students.index',
                'cta_route_fallback' => 'dashboard',
                'expert_service' => 'referral_campaign',
            ],
            'reviews' => [
                'effort' => 'medium',
                'title' => 'Collect more Google reviews',
                'why' => 'More reviews push you higher in "library near me" searches.',
                'diy_steps' => [
                    'Copy your Google review link into Settings → Website.',
                    'Send the review message to members on WhatsApp.',
                    'Update your review count as it grows.',
                ],
                'cta_label' => 'Open Website settings',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'reputation',
            ],
            'whatsapp' => [
                'effort' => 'medium',
                'title' => 'Send a promotion to your students',
                'why' => 'A monthly offer message keeps seats filled.',
                'diy_steps' => [
                    'Open the Promotion page.',
                    'Write an offer (new batch, festival discount, renewal reward).',
                    'Send it — it counts toward your score automatically.',
                ],
                'cta_label' => 'Open Promotion',
                'cta_route' => 'promotion.index',
                'expert_service' => 'whatsapp_marketing',
            ],
            'enquiries' => [
                'effort' => 'medium',
                'title' => 'Get new enquiries flowing',
                'why' => 'Regular enquiries are the first step to new memberships.',
                'diy_steps' => [
                    'Share your website Enquire form on WhatsApp and Instagram.',
                    'Add walk-in enquiries in the Enquiries page.',
                    'Follow up on new enquiries within 2 days.',
                ],
                'cta_label' => 'Open Enquiries',
                'cta_route' => 'enquiries.index',
                'expert_service' => 'lead_generation',
            ],
            'local_seo' => [
                'effort' => 'hard',
                'title' => 'Rank for "library near me"',
                'why' => 'Local SEO and ads bring students who are searching right now.',
                'diy_steps' => [
                    'Keep Google profile, website, and reviews up to date.',
                    'Post updates on Google Business Profile every week.',
                    'Request a managed package for SEO and ads.',
                ],
                'cta_label' => 'Open Website settings',
                'cta_route' => 'settings.index',
                'cta_route_params' => ['tab' => 'website'],
                'expert_service' => 'local_seo',
            ],
        ],
    ],

    'ad_spend_disclaimer' => 'Ad budget is billed separately from the service fee.',

    'gst_disclaimer' => 'Prices exclude GST.',

    'whatsapp_phone' => env('LIBCONTROL_GROWTH_WHATSAPP', env('LIBCONTROL_SUPPORT_WHATSAPP', '8076105181')),

    // Inbox that receives an email for every package / service request (comma-separate for several).
    'notify_email' => env('LIBCONTROL_GROWTH_NOTIFY_EMAIL', 'support@libcontrol.in'),

    'packages' => [
        'starter' => [
            'name' => 'Starter',
            'price' => 2999,
            'price_label' => '₹2,999/month',
            'billing' => 'monthly',
            'tagline' => 'Essential local visibility for one reading centre.',
            'includes' => [
                'WhatsApp promotional campaign (managed outreach)',
                '4 social media creatives per month',
                'Google Business Profile posts',
                'Review-generation campaign support',
                'Monthly marketing performance report',
            ],
        ],
        'grow' => [
            'name' => 'Grow',
            'price' => 7999,
            'price_label' => '₹7,999/month',
            'billing' => 'monthly',
            'tagline' => 'More creatives, SEO, and enquiry tracking.',
            'includes_package' => 'starter',
            'includes' => [
                'Everything in Starter',
                '8–10 social media creatives per month',
                '2 short reels',
                'Local SEO (keywords, on-page and directories)',
                'Google Business optimisation',
                'WhatsApp campaigns and lead / enquiry tracking',
            ],
        ],
        'dominate' => [
            'name' => 'Dominate',
            'price' => 14999,
            'price_label' => '₹14,999/month',
            'billing' => 'monthly',
            'tagline' => 'Full local ads + admission campaigns.',
            'includes_package' => 'grow',
            'includes' => [
                'Everything in Grow',
                'Google Ads campaign management',
                'Facebook / Instagram Ads',
                '4 reels',
                'Local SEO',
                'WhatsApp marketing',
                'Monthly lead report',
                'Admission / membership campaign',
            ],
            'notes' => ['Ad budget billed separately.'],
        ],
        'payment_test' => [
            'name' => '₹1 test pack',
            'price' => 1,
            'price_label' => '₹1 (one-time test)',
            'billing' => 'one_time',
            'demo_only' => true,
            'skip_profile_activation' => true,
            'tagline' => 'Tiny charge to verify PhonePe checkout on demo.libcontrol.in only.',
            'includes' => [
                'One-time ₹1 payment test',
                'No marketing deliverables',
                'Use before switching PhonePe to live mode',
            ],
            'notes' => ['Demo site only. Not a real growth package.'],
        ],
    ],

    'services' => require __DIR__.'/growth-services.php',

    'actions' => [
        'whatsapp' => [
            'label' => 'WhatsApp Campaign',
            'icon' => 'mail',
            'blurb' => 'Message local contacts with membership offers.',
            'diy' => 'review_kit',
            'service' => 'whatsapp_marketing',
        ],
        'google_search' => [
            'label' => 'Google Search Promotion',
            'icon' => 'chart',
            'blurb' => 'Appear when students search for libraries nearby.',
            'service' => 'google_ads',
        ],
        'google_maps' => [
            'label' => 'Google Maps Growth',
            'icon' => 'branch',
            'blurb' => 'Strengthen your listing and photos to convert Maps views into visits.',
            'service' => 'gbp_boost',
        ],
        'social' => [
            'label' => 'Social Media Promotion',
            'icon' => 'users',
            'blurb' => 'Facebook & Instagram creatives for your area.',
            'service' => 'social_marketing',
        ],
        'reviews' => [
            'label' => 'Get More Reviews',
            'icon' => 'ticket',
            'blurb' => 'Ask happy members for Google reviews.',
            'diy' => 'review_kit',
            'service' => 'reputation',
        ],
        'leads' => [
            'label' => 'Student Lead Generation',
            'icon' => 'inbox',
            'blurb' => 'Paid lead campaigns for new memberships.',
            'service' => 'lead_generation',
        ],
        'referral' => [
            'label' => 'Referral Campaign',
            'icon' => 'currency',
            'blurb' => 'Reward students who bring friends.',
            'diy' => 'referral',
            'service' => 'referral_campaign',
        ],
        'reels' => [
            'label' => 'Reels & Video Promotion',
            'icon' => 'grid',
            'blurb' => 'Show your halls and ambience in short videos.',
            'service' => 'video_reels',
        ],
    ],
];
