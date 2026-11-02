<?php

return [
    'attendance' => [
        'name' => 'Attendance',
        'description' => 'Student check-in via branch QR (mobile web) and staff GPS marking from the Android app.',
        'version' => '1.0.0',
        'provider' => \App\Addons\Attendance\AttendanceAddonServiceProvider::class,
        'migrations_path' => 'database/migrations/addons/attendance',
        'settings_route' => 'attendance.settings',
    ],
    'activity-log' => [
        'name' => 'Activity Log',
        'description' => 'Browse and review staff and admin activity across your library branches.',
        'version' => '1.0.0',
        'provider' => \App\Addons\ActivityLog\ActivityLogAddonServiceProvider::class,
    ],
];
