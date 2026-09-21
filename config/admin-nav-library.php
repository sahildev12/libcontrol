<?php

return [
    'primary' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Branch', 'route' => 'branch.index', 'icon' => 'branch', 'client_admin_only' => true],
        ['label' => 'Halls', 'route' => 'halls.index', 'icon' => 'building'],
        ['label' => 'Seats', 'route' => 'seats.index', 'icon' => 'grid'],
        ['label' => 'Trial Seats', 'route' => 'trial-seats.index', 'icon' => 'clock'],
        ['label' => 'Students', 'route' => 'students.index', 'icon' => 'users'],
        ['label' => 'Fee Management', 'route' => 'fees.index', 'icon' => 'currency'],
        ['label' => 'Profit-Loss Manage', 'route' => 'profit-loss.index', 'icon' => 'chart'],
        ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'clock', 'addon' => 'attendance'],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'icon' => 'chart'],
    ],
];
