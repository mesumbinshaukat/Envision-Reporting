<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Registry
    |--------------------------------------------------------------------------
    |
    | This file is the single source of truth for all permission-able features.
    | Each feature supports:
    | - read  : can view/list/export
    | - write : can create/update/delete/process (write implicitly includes read)
    |
    | Use these keys everywhere (middleware, UI, tests).
    */

    'features' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'group' => 'General',
        ],
        'clients' => [
            'label' => 'Clients',
            'group' => 'Sales',
        ],
        'invoices' => [
            'label' => 'Invoices',
            'group' => 'Sales',
        ],
        'employees' => [
            'label' => 'Employees',
            'group' => 'HR',
        ],
        'expenses' => [
            'label' => 'Expenses',
            'group' => 'Finance',
        ],
        'allowances' => [
            'label' => 'Allowances',
            'group' => 'Finance',
        ],
        'bonuses' => [
            'label' => 'Bonuses',
            'group' => 'Finance',
        ],
        'salary_releases' => [
            'label' => 'Salary Releases',
            'group' => 'Finance',
        ],
        'reports' => [
            'label' => 'Reports',
            'group' => 'Finance',
        ],
        'currencies' => [
            'label' => 'Currency',
            'group' => 'Finance',
        ],
        'attendance_employee' => [
            'label' => 'Attendance (Employee)',
            'group' => 'Attendance',
        ],
        'attendance_admin' => [
            'label' => 'Attendance (Admin)',
            'group' => 'Attendance',
        ],
        'office_location' => [
            'label' => 'Office Location & Enforcement',
            'group' => 'Attendance',
        ],
        'attendance_logs' => [
            'label' => 'Attendance Logs',
            'group' => 'Attendance',
        ],
        'activity_logs' => [
            'label' => 'Employee Activity Logs',
            'group' => 'Attendance',
        ],
        'users' => [
            'label' => 'User Management',
            'group' => 'Administration',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Defaults
    |--------------------------------------------------------------------------
    |
    | Defaults applied when creating a user with an empty permission set.
    | Admin always has full access implicitly (no need to store rows).
    */
    'role_defaults' => [
        'supervisor' => [
            // sensible read-most defaults
            'dashboard' => ['read' => true, 'write' => false],
            'clients' => ['read' => true, 'write' => false],
            'invoices' => ['read' => true, 'write' => false],
            'employees' => ['read' => true, 'write' => false],
            'expenses' => ['read' => true, 'write' => false],
            'allowances' => ['read' => true, 'write' => false],
            'bonuses' => ['read' => true, 'write' => false],
            'salary_releases' => ['read' => true, 'write' => false],
            'reports' => ['read' => true, 'write' => false],
            'currencies' => ['read' => true, 'write' => false],
            'attendance_admin' => ['read' => true, 'write' => false],
            'office_location' => ['read' => true, 'write' => false],
            'attendance_logs' => ['read' => true, 'write' => false],
            'activity_logs' => ['read' => true, 'write' => false],
        ],
        'moderator' => [
            // empty by default; must be explicitly assigned
        ],
    ],
];

