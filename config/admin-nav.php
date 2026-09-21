<?php

return [
    'primary' => [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Branch', 'route' => 'branch.index', 'icon' => 'branch', 'client_admin_only' => true],
        ['label' => 'Halls', 'route' => 'halls.index', 'icon' => 'building', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Seats', 'route' => 'seats.index', 'icon' => 'grid', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Trial Seats', 'route' => 'trial-seats.index', 'icon' => 'clock', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Students', 'route' => 'students.index', 'icon' => 'users', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Fee Management', 'route' => 'fees.index', 'icon' => 'currency', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Finance', 'route' => 'finance.index', 'icon' => 'finance', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'clock', 'addon' => 'attendance', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Promotion', 'route' => 'promotion.index', 'icon' => 'mail', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Activity Log', 'route' => 'activity-logs.index', 'icon' => 'chart', 'client_admin_only' => true],
        ['label' => 'Help & Support', 'route' => 'help-support.index', 'icon' => 'ticket', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings', 'client_admin_only' => true, 'branch_staff_only' => true],
        ['label' => 'Support Tickets', 'route' => 'developer.support-tickets.index', 'icon' => 'ticket', 'developer_admin_only' => true, 'license_server_only' => true],
        ['label' => 'Portal Settings', 'route' => 'developer.portals.index', 'icon' => 'settings', 'developer_admin_only' => true, 'tenancy_only' => true],
        ['label' => 'Client Libraries', 'route' => 'developer.tenants.index', 'icon' => 'branch', 'developer_admin_only' => true, 'tenancy_only' => true],
        ['label' => 'Dev & Domains', 'route' => 'developer.deployments.index', 'icon' => 'branch', 'developer_admin_only' => true, 'license_server_only' => true],
    ],
];
