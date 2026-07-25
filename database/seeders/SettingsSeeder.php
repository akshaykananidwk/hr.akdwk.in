<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'company_name', 'value' => 'AK Computer', 'group' => 'company'],
            ['key' => 'company_tagline', 'value' => 'Enterprise Workforce & Sales CRM', 'group' => 'company'],
            ['key' => 'company_email', 'value' => 'info@akcomputer.in', 'group' => 'company'],
            ['key' => 'company_phone', 'value' => '', 'group' => 'company'],
            ['key' => 'company_address', 'value' => 'Gujarat, India', 'group' => 'company'],
            ['key' => 'currency', 'value' => 'INR', 'group' => 'company'],
            ['key' => 'currency_symbol', 'value' => '₹', 'group' => 'company'],
            ['key' => 'timezone', 'value' => 'Asia/Kolkata', 'group' => 'company'],
            ['key' => 'work_start_time', 'value' => '09:30', 'group' => 'attendance'],
            ['key' => 'late_after_time', 'value' => '09:45', 'group' => 'attendance'],
            ['key' => 'full_day_hours', 'value' => '8', 'group' => 'attendance'],
            // GitHub auto-update configuration (token stored encrypted via Setting::put)
            ['key' => 'github_repo', 'value' => 'akshaykananidwk/hr.akdwk.in', 'group' => 'update'],
            ['key' => 'github_branch', 'value' => 'main', 'group' => 'update'],
            ['key' => 'app_version', 'value' => '1.0.0', 'group' => 'update'],
            ['key' => 'current_commit', 'value' => '', 'group' => 'update'],
        ];

        foreach ($defaults as $row) {
            Setting::query()->firstOrCreate(['key' => $row['key']], [
                'value' => $row['value'],
                'group' => $row['group'],
            ]);
        }

        // Policy documents shown on the digital-agreement screen.
        $policies = [
            'appointment_letter' => 'Appointment Letter',
            'employment_agreement' => 'Employment Agreement',
            'nda' => 'Non-Disclosure Agreement (NDA)',
            'company_policy' => 'Company Policy',
            'leave_policy' => 'Leave Policy',
            'gps_policy' => 'GPS / Location Tracking Policy',
            'privacy_policy' => 'Privacy Policy',
            'target_policy' => 'Target Policy',
            'commission_policy' => 'Commission Policy',
            'employee_handbook' => 'Employee Handbook',
        ];
        foreach ($policies as $key => $title) {
            Setting::query()->firstOrCreate(['key' => "policy_{$key}"], [
                'group' => 'policy',
                'value' => "## {$title}\n\nThis is the default {$title} for AK Computer. "
                    .'Administrators can edit the full text from Settings → Policies. '
                    ."By accepting, the employee acknowledges they have read and agree to the terms of this {$title}.",
            ]);
        }
    }
}
