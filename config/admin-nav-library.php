<?php

return [
    'primary' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Branch', 'route' => 'branch.index', 'icon' => 'branch', 'platform_admin_only' => true],
        ['label' => 'Halls', 'route' => 'halls.index', 'icon' => 'building'],
        ['label' => 'Seats', 'route' => 'seats.index', 'icon' => 'grid'],
        ['label' => 'Trial Seats', 'route' => 'trial-seats.index', 'icon' => 'clock'],
        ['label' => 'Students', 'route' => 'students.index', 'icon' => 'users'],
        ['label' => 'Fee Management', 'route' => 'fees.index', 'icon' => 'currency'],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell'],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'icon' => 'chart'],
        ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
    ],
];
