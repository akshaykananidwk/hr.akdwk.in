<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Holiday;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::firstOrCreate(['name' => 'Head Office'], [
            'code' => 'HO', 'city' => 'Ahmedabad', 'state' => 'Gujarat',
            'address' => 'AK Computer, Gujarat, India', 'phone' => '',
            'latitude' => 23.022505, 'longitude' => 72.571362, 'geofence_radius' => 300,
        ]);

        $departments = [
            'Sales' => ['Sales Executive', 'Team Leader', 'Sales Manager'],
            'Support' => ['Support Executive', 'Support Lead'],
            'Human Resources' => ['HR Executive', 'HR Manager'],
            'Accounts' => ['Accountant', 'Accounts Manager'],
            'Administration' => ['Admin', 'Operations Head'],
        ];

        foreach ($departments as $deptName => $designations) {
            $dept = Department::firstOrCreate(['name' => $deptName], ['code' => strtoupper(substr($deptName, 0, 3))]);
            foreach ($designations as $desig) {
                Designation::firstOrCreate(['name' => $desig, 'department_id' => $dept->id]);
            }
        }

        // Holidays (sample)
        $holidays = [
            ['name' => 'Republic Day', 'date' => '2026-01-26'],
            ['name' => 'Holi', 'date' => '2026-03-04'],
            ['name' => 'Independence Day', 'date' => '2026-08-15'],
            ['name' => 'Diwali', 'date' => '2026-11-08'],
        ];
        foreach ($holidays as $h) {
            Holiday::firstOrCreate(['date' => $h['date']], ['name' => $h['name']]);
        }

        // Products
        $products = [
            [
                'name' => 'Krishna AI', 'slug' => 'krishna-ai',
                'tagline' => 'Bulk WhatsApp, Chatbot & Automation',
                'description' => 'AI-powered WhatsApp automation suite: bulk messaging, chatbot, automation flows and API access.',
                'features' => ['Bulk WhatsApp', 'AI Chatbot', 'Automation Flows', 'REST API', 'Campaign Analytics'],
                'plans' => [
                    ['name' => 'Starter', 'price' => 2999, 'period' => 'monthly'],
                    ['name' => 'Business', 'price' => 5999, 'period' => 'monthly'],
                    ['name' => 'Enterprise', 'price' => 11999, 'period' => 'monthly'],
                ],
                'commission_type' => 'percentage', 'commission_value' => 15, 'renewal_commission_value' => 8,
            ],
            [
                'name' => 'Krishna Review System', 'slug' => 'krishna-review',
                'tagline' => 'Google Reviews, QR Review & AI Review',
                'description' => 'Collect and grow Google reviews with QR codes and AI-assisted review generation.',
                'features' => ['Google Reviews', 'QR Review Cards', 'AI Review Suggestions', 'Negative Feedback Filter'],
                'plans' => [
                    ['name' => 'Basic', 'price' => 1499, 'period' => 'monthly'],
                    ['name' => 'Pro', 'price' => 2999, 'period' => 'monthly'],
                ],
                'commission_type' => 'percentage', 'commission_value' => 20, 'renewal_commission_value' => 10,
            ],
            [
                'name' => 'Krishna Menu', 'slug' => 'krishna-menu',
                'tagline' => 'QR Menu & Restaurant Ordering',
                'description' => 'Digital QR menu and ordering system for restaurants and cafes.',
                'features' => ['QR Digital Menu', 'Online Ordering', 'Table Management', 'Menu Analytics'],
                'plans' => [
                    ['name' => 'Standard', 'price' => 999, 'period' => 'monthly'],
                    ['name' => 'Premium', 'price' => 1999, 'period' => 'monthly'],
                ],
                'commission_type' => 'percentage', 'commission_value' => 18, 'renewal_commission_value' => 9,
            ],
        ];
        foreach ($products as $p) {
            Product::firstOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
