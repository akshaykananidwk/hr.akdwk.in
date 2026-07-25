<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Full seed for local development (roles + settings + org + demo data + a Super Admin).
     * The web installer runs the essential seeders individually and creates its own admin.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            OrganizationSeeder::class,
        ]);

        $admin = User::firstOrCreate(['email' => 'admin@akcomputer.in'], [
            'name' => 'Super Admin',
            'employee_code' => 'AK0001',
            'password' => Hash::make('password'),
            'branch_id' => Branch::first()?->id,
            'department_id' => Department::where('name', 'Administration')->first()?->id,
            'date_of_joining' => now(),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles(['Super Admin']);
        EmployeeProfile::firstOrCreate(['user_id' => $admin->id]);

        $this->call([DemoSeeder::class]);
    }
}
