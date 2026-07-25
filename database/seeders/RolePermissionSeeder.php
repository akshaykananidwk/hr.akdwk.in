<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** The 9 application roles. */
    public const ROLES = [
        'Super Admin', 'HR Manager', 'Sales Manager', 'Team Leader',
        'Sales Executive', 'Support Executive', 'Accountant', 'Employee', 'Viewer',
    ];

    /** Granular permissions grouped by module. */
    public const PERMISSIONS = [
        'view dashboard',
        'manage employees', 'view employees',
        'manage documents', 'verify documents',
        'manage attendance', 'view attendance',
        'submit reports', 'view reports',
        'manage leads', 'view leads',
        'manage sales', 'view sales',
        'manage tasks', 'view tasks',
        'apply leave', 'approve leave',
        'manage products', 'view products',
        'view commissions', 'manage commissions',
        'manage payroll', 'view payroll',
        'manage targets', 'view targets',
        'manage settings', 'manage updates',
        'view activity log',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $roles = [];
        foreach (self::ROLES as $roleName) {
            $roles[$roleName] = Role::findOrCreate($roleName, 'web');
        }

        // Super Admin gets everything.
        $roles['Super Admin']->syncPermissions(Permission::all());

        $roles['HR Manager']->syncPermissions([
            'view dashboard', 'manage employees', 'view employees', 'manage documents',
            'verify documents', 'manage attendance', 'view attendance', 'view reports',
            'approve leave', 'apply leave', 'manage payroll', 'view payroll',
            'manage tasks', 'view tasks', 'view activity log',
        ]);

        $roles['Sales Manager']->syncPermissions([
            'view dashboard', 'view employees', 'manage leads', 'view leads', 'manage sales',
            'view sales', 'manage tasks', 'view tasks', 'manage targets', 'view targets',
            'view commissions', 'manage commissions', 'view reports', 'view attendance',
        ]);

        $roles['Team Leader']->syncPermissions([
            'view dashboard', 'view employees', 'manage leads', 'view leads', 'manage tasks',
            'view tasks', 'view targets', 'view reports', 'submit reports', 'apply leave',
            'view attendance',
        ]);

        $roles['Sales Executive']->syncPermissions([
            'view dashboard', 'manage leads', 'view leads', 'submit reports', 'view reports',
            'manage attendance', 'view tasks', 'apply leave', 'view products', 'view targets',
            'view commissions', 'view sales',
        ]);

        $roles['Support Executive']->syncPermissions([
            'view dashboard', 'submit reports', 'view reports', 'manage attendance',
            'view tasks', 'apply leave', 'view products',
        ]);

        $roles['Accountant']->syncPermissions([
            'view dashboard', 'view sales', 'manage sales', 'view commissions', 'manage commissions',
            'manage payroll', 'view payroll', 'view reports',
        ]);

        $roles['Employee']->syncPermissions([
            'view dashboard', 'submit reports', 'manage attendance', 'view tasks', 'apply leave',
        ]);

        $roles['Viewer']->syncPermissions([
            'view dashboard', 'view reports', 'view leads', 'view sales', 'view attendance',
        ]);
    }
}
