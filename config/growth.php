<?php

return [
    'enabled' => filter_var(env('LIBCONTROL_GROWTH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

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
        'effort_order' => ['easy' => 0, 'medium' => 1, 'hard' => 2],
        'items' => [
            'instagram' => [
                'effort' => 'easy',
                'title' => 'Add your Instagram page',
                'why' => 'Students check Instagram before visiting a library.',
                'diy_steps' => [
                    'Open your library Instagram profile and copy its URL.',
                    'Paste it in Grow My Library or Settings → Website → Social links.',
                    'Save to refresh your score.',
                ],
                'expert_service' => 'social_marketing',
            ],
            'facebook' => [
                'effort' => 'easy',
                'title' => 'Add your Facebook page',
                'why' => 'A Facebook page builds trust with parents and local students.',
                'diy_steps' => [
                    'Create or open your library Facebook page.',
                    'Paste the page URL in Grow My Library or Settings → Website.',
                    'Save to refresh your score.',
                ],
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
                'expert_service' => 'website_landing',
            ],
            'gbp' => [
                'effort' => 'easy',
                'title' => 'Complete your Google Business Profile',
                'why' => 'Most students find libraries through Google Maps.',
                'diy_steps' => [
                    'Claim your library on Google Business Profile.',
                    'Add photos, the right category, and opening hours.',
                    'Paste your Google Maps listing link in the Visibility checklist, then tick each item.',
                ],
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
                'expert_service' => 'referral_campaign',
            ],
            'reviews' => [
                'effort' => 'medium',
                'title' => 'Collect more Google reviews',
                'why' => 'More reviews push you higher in "library near me" searches.',
                'diy_steps' => [
                    'Copy your Google review link into Grow My Library.',
                    'Send the review message to members on WhatsApp.',
                    'Update your review count as it grows.',
                ],
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
                'expert_service' => 'local_seo',
            ],
        ],
    ],

    'ad_spend_disclaimer' => 'Ad budget is billed separately from the service fee.',

    'gst_disclaimer' => 'Prices exclude GST.',

    'whatsapp_phone' => env('LIBCONTROL_GROWTH_WHATSAPP', env('LIBCONTROL_SUPPORT_WHATSAPP', '8076105181')),

    // Inbox that receives an email for every package / service request (comma-separate for several).
    'notify_email' => env('LIBCONTROL_GROWTH_NOTIFY_EMAIL', 'contact@phenomit.com'),

    'razorpay' => [
        'enabled' => filter_var(env('LIBCONTROL_GROWTH_RAZORPAY_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'currency' => 'INR',
        // Optional hosted subscription / payment-link IDs created in Razorpay Dashboard
        'subscription_plan_ids' => [
            'starter' => env('RAZORPAY_PLAN_GROWTH_STARTER'),
            'grow' => env('RAZORPAY_PLAN_GROWTH_GROW'),
            'dominate' => env('RAZORPAY_PLAN_GROWTH_DOMINATE'),
        ],
        'payment_page_urls' => [
            'starter' => env('RAZORPAY_PAGE_GROWTH_STARTER'),
            'grow' => env('RAZORPAY_PAGE_GROWTH_GROW'),
            'dominate' => env('RAZORPAY_PAGE_GROWTH_DOMINATE'),
        ],
    ],

    'packages' => [
        'starter' => [
            'name' => 'Starter',
            'price' => 2999,
            'price_label' => '₹2,999/month',
            'billing' => 'monthly',
            'tagline' => 'Essential local visibility for one reading centre.',
            'includes' => [
                'WhatsApp promotional campaign',
                '4 social media creatives',
                'Google Business Profile posts',
                'Review-generation campaign',
                'Monthly marketing report',
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
                '8–10 social media creatives',
                '2 reels',
                'Local SEO',
                'Google Business optimization',
                'WhatsApp campaigns',
                'Lead / enquiry tracking',
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
    ],

    'services' => [
        'whatsapp_marketing' => [
            'name' => 'WhatsApp Marketing',
            'description' => 'Promotional creatives + WhatsApp campaigns to local students/contacts',
            'price_label' => '₹2,500–₹5,000/month',
            'billing' => 'monthly',
        ],
        'gbp_boost' => [
            'name' => 'Google Business Profile Boost',
            'description' => 'Optimize Google Maps profile, photos, posts, reviews & local visibility',
            'price_label' => '₹2,500/month',
            'billing' => 'monthly',
        ],
        'google_ads' => [
            'name' => 'Google Ads – Local Leads',
            'description' => 'Search ads targeting students searching for libraries nearby',
            'price_label' => '₹5,000–₹10,000/month + ad spend',
            'billing' => 'monthly',
            'ad_spend' => true,
        ],
        'social_marketing' => [
            'name' => 'Facebook & Instagram Marketing',
            'description' => '8–12 creatives/reels + posting + local targeting',
            'price_label' => '₹5,000–₹10,000/month',
            'billing' => 'monthly',
        ],
        'local_seo' => [
            'name' => 'Local SEO',
            'description' => '“Library near me”, “Study library in Hisar” and similar keywords',
            'price_label' => '₹5,000–₹8,000/month',
            'billing' => 'monthly',
        ],
        'website_landing' => [
            'name' => 'Website / Landing Page',
            'description' => 'Professional library website with enquiry / WhatsApp button',
            'price_label' => '₹10,000–₹25,000 one-time',
            'billing' => 'one_time',
        ],
        'lead_generation' => [
            'name' => 'Student Lead Generation',
            'description' => 'Facebook/Instagram lead campaigns for new memberships',
            'price_label' => '₹5,000–₹10,000 + ad spend',
            'billing' => 'monthly',
            'ad_spend' => true,
        ],
        'reputation' => [
            'name' => 'Review & Reputation Marketing',
            'description' => 'Assisted review requests + testimonial creatives',
            'price_label' => '₹1,500–₹3,000/month',
            'billing' => 'monthly',
        ],
        'festival_campaign' => [
            'name' => 'Festival & Admission Campaigns',
            'description' => 'Special campaigns for exams, admissions, new batches',
            'price_label' => '₹2,500–₹5,000/campaign',
            'billing' => 'campaign',
        ],
        'referral_campaign' => [
            'name' => 'Referral Campaign',
            'description' => '“Refer a friend & get ₹X off” campaign + tracking',
            'price_label' => '₹2,500 setup',
            'billing' => 'setup',
        ],
        'branding_kit' => [
            'name' => 'Library Branding Kit',
            'description' => 'Logo, posters, membership cards, social templates, banners',
            'price_label' => '₹5,000–₹15,000',
            'billing' => 'one_time',
        ],
        'video_reels' => [
            'name' => 'Video / Reels Marketing',
            'description' => 'Short promotional videos showing facilities and ambience',
            'price_label' => '₹2,000–₹5,000/video',
            'billing' => 'campaign',
        ],
    ],

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
