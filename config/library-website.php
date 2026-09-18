<?php

/**
 * Public library website content defaults.
 * Client-facing study center site — not LibControl product marketing.
 * Override identity fields via Platform Settings where noted.
 */
return [
    'library_name' => 'THE STUDY HUB',
    'subtitle' => 'Library & Study Center',
    'tagline' => 'A Quiet Place for Bigger Dreams.',
    'footer_tagline' => 'A quiet place for bigger dreams.',

    'phone' => '+91 98765 43210',
    'phone_href' => 'tel:+919876543210',
    'email' => 'info@thestudyhub.in',
    'whatsapp' => '9876543210',
    'address' => [
        'line1' => '123 Study Lane,',
        'line2' => 'Green Park,',
        'line3' => 'Your City, 123001',
    ],
    'opening_hours' => '6:00 AM – 11:00 PM',
    'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.0!2d77.2090!3d28.6139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjjCsDM2JzUwLjAiTiA3N8KwMTInMzIuNCJF!5e0!3m2!1sen!2sin!4v1600000000000!5m2!1sen!2sin',
    'map_directions_url' => 'https://maps.google.com/?q=123+Study+Lane+Green+Park',

    'social_links' => [
        'instagram' => 'https://instagram.com/thestudyhub',
        'facebook' => 'https://facebook.com/thestudyhub',
        'youtube' => 'https://youtube.com/@thestudyhub',
    ],

    'seo' => [
        'title' => 'The Study Hub | Library & Study Center',
        'description' => 'A peaceful and modern study library with comfortable seating, high-speed internet, flexible memberships and a focused learning environment.',
    ],

    'navigation' => [
        ['label' => 'Home', 'href' => '#home'],
        ['label' => 'About', 'href' => '#about'],
        ['label' => 'Facilities', 'href' => '#facilities'],
        ['label' => 'Gallery', 'href' => '#gallery'],
        ['label' => 'Membership', 'href' => '#membership'],
        ['label' => 'Rules', 'href' => '#rules'],
        ['label' => 'Contact', 'href' => '#contact'],
    ],

    'hero' => [
        'label' => 'WELCOME TO THE STUDY HUB',
        'heading_line1' => 'A Quiet Place',
        'heading_line2' => 'For Bigger Dreams',
        'description' => 'More than just a library — a peaceful space where students can focus, learn and grow.',
        'primary_cta' => ['label' => 'Join Now', 'href' => '#membership'],
        'secondary_cta' => ['label' => 'Take a Tour', 'href' => '#gallery'],
        'quote' => 'Discipline Today, Success Tomorrow.',
        'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b04020?auto=format&fit=crop&w=1920&q=80',
        'image_alt' => 'Students studying in a warm, well-lit library hall',
        'benefits' => [
            ['icon' => 'peace', 'label' => 'Peaceful Environment'],
            ['icon' => 'wifi', 'label' => 'High-Speed WiFi'],
            ['icon' => 'secure', 'label' => 'Safe & Secure'],
        ],
    ],

    'about' => [
        'label' => 'ABOUT US',
        'heading_line1' => 'Study Today.',
        'heading_line2' => 'Build a Brighter Tomorrow.',
        'paragraph' => 'Our library is designed for serious learners who want a peaceful and motivating environment. From comfortable seating and high-speed internet to clean facilities and a supportive study atmosphere, everything is designed to help you stay focused.',
        'cta' => ['label' => 'Know More About Us', 'href' => '#contact'],
        'quote' => 'A library is not just a place to read, but a place to become a better version of yourself.',
        'image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?auto=format&fit=crop&w=1200&q=80',
        'image_alt' => 'Comfortable study desks in a modern library interior',
        'stats' => [
            ['value' => '500+', 'label' => 'Active Members'],
            ['value' => '200+', 'label' => 'Study Seats'],
            ['value' => '5+', 'label' => 'Years of Service'],
        ],
    ],

    'facilities' => [
        'heading' => 'Everything You Need',
        'heading_highlight' => 'For a Productive Study Experience',
        'description' => 'Thoughtfully designed spaces and amenities so you can sit down, open your books, and stay in the zone.',
        'cta' => ['label' => 'View All Facilities', 'href' => '#contact'],
        'items' => [
            ['icon' => 'seat', 'name' => 'Comfortable Seating', 'description' => 'Ergonomic chairs and spacious desks for long study sessions.'],
            ['icon' => 'wifi', 'name' => 'High-Speed Internet', 'description' => 'Reliable Wi-Fi for research, lectures, and online learning.'],
            ['icon' => 'ac', 'name' => 'AC Study Halls', 'description' => 'Climate-controlled halls that stay comfortable all day.'],
            ['icon' => 'washroom', 'name' => 'Clean Washrooms', 'description' => 'Well-maintained facilities for a hygienic experience.'],
            ['icon' => 'water', 'name' => 'Drinking Water', 'description' => 'Filtered water available throughout the library.'],
            ['icon' => 'cctv', 'name' => 'CCTV Security', 'description' => 'Monitored premises for a safe study environment.'],
            ['icon' => 'power', 'name' => 'Power Backup', 'description' => 'Uninterrupted power so your study never stops.'],
            ['icon' => 'peace', 'name' => 'Peaceful Environment', 'description' => 'A calm atmosphere built for deep focus.'],
        ],
    ],

    'gallery' => [
        'heading' => 'A Glimpse of Our Library',
        'description' => 'Take a look inside — warm lighting, organized spaces, and a setting made for concentration.',
        'cta' => ['label' => 'View Full Gallery', 'href' => '#gallery'],
        'images' => [
            ['src' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b04020?auto=format&fit=crop&w=900&q=80', 'alt' => 'Main study hall with bookshelves'],
            ['src' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=900&q=80', 'alt' => 'Library bookshelves and reading area'],
            ['src' => 'https://images.unsplash.com/photo-1568667256549-094345857637?auto=format&fit=crop&w=900&q=80', 'alt' => 'Individual study desks'],
            ['src' => 'https://images.unsplash.com/photo-1524995997942-9eca77b42e16?auto=format&fit=crop&w=900&q=80', 'alt' => 'Quiet reading area'],
            ['src' => 'https://images.unsplash.com/photo-1507842217343-583bb7270bba?auto=format&fit=crop&w=900&q=80', 'alt' => 'Library entrance and reception area'],
            ['src' => 'https://images.unsplash.com/photo-1497633762263-9fc17901db2a?auto=format&fit=crop&w=900&q=80', 'alt' => 'Evening study environment'],
            ['src' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=900&q=80', 'alt' => 'Comfortable seating for students'],
            ['src' => 'https://images.unsplash.com/photo-1506880018603-83cc5ab44ffc?auto=format&fit=crop&w=900&q=80', 'alt' => 'Library interior details'],
        ],
    ],

    'membership_plans' => [
        'heading' => 'Flexible Plans',
        'heading_highlight' => 'For Every Learner',
        'description' => 'Choose a plan that fits your study needs and get access to all our facilities.',
        'benefits' => [
            'Access to all study areas',
            'High-speed WiFi',
            'Basic facilities',
            'Flexible study hours',
        ],
        'plans' => [
            [
                'name' => 'Monthly',
                'price' => '₹1,000',
                'period' => '/ month',
                'popular' => false,
            ],
            [
                'name' => 'Quarterly',
                'price' => '₹2,700',
                'period' => '/ 3 months',
                'popular' => true,
            ],
            [
                'name' => 'Half Yearly',
                'price' => '₹5,000',
                'period' => '/ 6 months',
                'popular' => false,
            ],
            [
                'name' => 'Yearly',
                'price' => '₹9,000',
                'period' => '/ year',
                'popular' => false,
            ],
        ],
    ],

    'testimonials' => [
        'heading' => 'Loved by Learners',
        'items' => [
            [
                'name' => 'Priya Sharma',
                'role' => 'Student',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=200&q=80',
                'quote' => 'The best library in the city. Peaceful environment and great facilities. It really helps me stay focused.',
            ],
            [
                'name' => 'Rahul Verma',
                'role' => 'Student',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80',
                'quote' => 'Clean, quiet, and well maintained. I come here every day during my exam preparation.',
            ],
            [
                'name' => 'Ananya Patel',
                'role' => 'Student',
                'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=200&q=80',
                'quote' => 'The staff is supportive and the seating is very comfortable. Highly recommended for serious learners.',
            ],
            [
                'name' => 'Karan Mehta',
                'role' => 'Student',
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=200&q=80',
                'quote' => 'Great Wi-Fi, AC halls, and a disciplined atmosphere. Exactly what I needed for competitive exams.',
            ],
        ],
    ],

    'social' => [
        'heading' => 'Our Moments',
        'heading_highlight' => 'On Social Media',
        'description' => 'Stay connected and see the latest updates, events and moments from our library.',
        'cta' => ['label' => 'Follow Us', 'href' => '#contact'],
        'posts' => [
            [
                'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b04020?auto=format&fit=crop&w=600&q=80',
                'caption' => 'A Focused Mind Builds a Brighter Future',
                'likes' => '248',
                'comments' => '18',
                'date' => '2 days ago',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=600&q=80',
                'caption' => 'Good Books. Good Habits.',
                'likes' => '192',
                'comments' => '12',
                'date' => '5 days ago',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?auto=format&fit=crop&w=600&q=80',
                'caption' => 'Your Study Space. Your Success.',
                'likes' => '315',
                'comments' => '24',
                'date' => '1 week ago',
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1497633762263-9fc17901db2a?auto=format&fit=crop&w=600&q=80',
                'caption' => 'Discipline Today. Success Tomorrow.',
                'likes' => '276',
                'comments' => '15',
                'date' => '1 week ago',
            ],
        ],
    ],

    'rules' => [
        'heading' => 'A Better Environment',
        'heading_highlight' => 'For Everyone',
        'description' => 'Simple guidelines that keep our library peaceful and welcoming for every learner.',
        'cta' => ['label' => 'View All Rules', 'href' => '#contact'],
        'items' => [
            ['icon' => 'silence', 'title' => 'Maintain Silence', 'description' => 'Keep noise low so everyone can concentrate.'],
            ['icon' => 'food', 'title' => 'No Food Allowed', 'description' => 'Please enjoy meals outside the study halls.'],
            ['icon' => 'clean', 'title' => 'Keep Your Seat Clean', 'description' => 'Leave your desk tidy for the next student.'],
            ['icon' => 'respect', 'title' => 'Respect Others', 'description' => 'Be considerate — we are all here to learn.'],
        ],
    ],

    'contact' => [
        'heading' => 'Visit Us',
        'subheading' => 'Our Location',
        'description' => 'Come and experience a better place to study. We\'re open every day.',
    ],
];
