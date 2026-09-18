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
        ['label' => 'Finance', 'route' => 'finance.index', 'icon' => 'finance'],
        ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'clock', 'addon' => 'attendance'],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell'],
        ['label' => 'Offers', 'route' => 'offers.index', 'icon' => 'offer'],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'icon' => 'chart'],
        ['label' => 'Help & Support', 'route' => 'help-support.index', 'icon' => 'ticket'],
        ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
        ['label' => 'Support Tickets', 'route' => 'developer.support-tickets.index', 'icon' => 'ticket', 'platform_admin_only' => true, 'developer_admin_only' => true, 'license_server_only' => true],
        ['label' => 'Client Libraries', 'route' => 'developer.tenants.index', 'icon' => 'branch', 'platform_admin_only' => true, 'developer_admin_only' => true, 'tenancy_only' => true],
        ['label' => 'Dev & Domains', 'route' => 'developer.deployments.index', 'icon' => 'branch', 'platform_admin_only' => true, 'developer_admin_only' => true, 'license_server_only' => true],
    ],
];
