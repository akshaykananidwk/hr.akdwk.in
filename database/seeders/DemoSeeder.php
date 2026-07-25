<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\DailyReport;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Target;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        $salesDept = Department::where('name', 'Sales')->first();
        $products = Product::all();

        $manager = $this->makeUser('Ravi Patel', 'manager@akcomputer.in', 'Sales Manager', $branch, $salesDept, 'Sales Manager');

        $executives = [];
        $names = ['Amit Shah', 'Priya Mehta', 'Kishan Desai', 'Neha Joshi', 'Raj Solanki'];
        foreach ($names as $i => $name) {
            $email = Str::slug(explode(' ', $name)[0]).'@akcomputer.in';
            $exec = $this->makeUser($name, $email, 'Sales Executive', $branch, $salesDept, 'Sales Executive', $manager->id);
            $executives[] = $exec;

            // Monthly sales target
            Target::firstOrCreate([
                'user_id' => $exec->id,
                'period_type' => 'monthly',
                'period_start' => Carbon::now()->startOfMonth()->toDateString(),
            ], [
                'period_end' => Carbon::now()->endOfMonth()->toDateString(),
                'metric' => 'sales_amount',
                'target_value' => 50000,
                'achieved_value' => rand(8000, 46000),
            ]);

            // Some attendance for the last 5 days
            for ($d = 0; $d < 5; $d++) {
                $date = Carbon::now()->subDays($d);
                if ($date->isWeekend()) {
                    continue;
                }
                Attendance::firstOrCreate([
                    'user_id' => $exec->id, 'date' => $date->toDateString(),
                ], [
                    'check_in_at' => $date->copy()->setTime(9, rand(25, 55)),
                    'check_out_at' => $date->copy()->setTime(18, rand(0, 45)),
                    'status' => 'present', 'method' => 'gps',
                    'working_hours' => 8.5, 'distance_travelled' => rand(5, 40),
                    'check_in_lat' => 23.02, 'check_in_lng' => 72.57,
                ]);
            }

            // A daily report today
            DailyReport::firstOrCreate([
                'user_id' => $exec->id, 'date' => Carbon::today()->toDateString(),
            ], [
                'start_time' => '09:30', 'end_time' => '18:30',
                'work_summary' => 'Field visits and product demos across the city.',
                'total_visits' => rand(4, 12), 'total_calls' => rand(10, 30),
                'total_followups' => rand(2, 8), 'total_demos' => rand(1, 4),
                'total_closings' => rand(0, 2), 'total_collection' => rand(0, 15000),
                'petrol_expense' => rand(80, 300),
            ]);

            // Tasks
            Task::firstOrCreate([
                'title' => "Follow up pending leads - {$name}", 'assigned_to' => $exec->id,
            ], [
                'assigned_by' => $manager->id, 'priority' => 'high', 'status' => 'pending',
                'due_date' => Carbon::now()->addDays(2)->toDateString(),
                'description' => 'Call all leads in negotiation stage and update status.',
            ]);
        }

        // Leads spread across executives / statuses / products
        $businesses = [
            'Shreeji Restaurant', 'Patel Electronics', 'Gujarat Sweets', 'Metro Cafe', 'Krishna Traders',
            'Ganesh Mobiles', 'Royal Salon', 'Anand Dairy', 'Silver Spoon', 'Urban Kirana',
        ];
        $statuses = Lead::STATUSES;
        foreach ($businesses as $i => $biz) {
            $exec = $executives[$i % count($executives)];
            $status = $statuses[$i % count($statuses)];
            $product = $products[$i % $products->count()];
            $lead = Lead::firstOrCreate(['business_name' => $biz], [
                'name' => 'Owner of '.$biz,
                'phone' => '98'.rand(10000000, 99999999),
                'source' => ['manual', 'whatsapp', 'reference', 'cold_visit'][$i % 4],
                'status' => $status,
                'product_id' => $product->id,
                'assigned_to' => $exec->id,
                'created_by' => $manager->id,
                'expected_value' => $product->plans[0]['price'] ?? 2999,
                'next_followup_at' => Carbon::now()->addDays(rand(1, 7)),
                'notes' => 'Interested in '.$product->name,
            ]);

            // Convert "won" leads into sales + commission
            if ($status === 'won') {
                $amount = $product->plans[0]['price'] ?? 2999;
                $sale = Sale::firstOrCreate(['invoice_number' => 'INV-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT)], [
                    'lead_id' => $lead->id, 'product_id' => $product->id, 'user_id' => $exec->id,
                    'customer_name' => $biz, 'customer_phone' => $lead->phone,
                    'plan_name' => $product->plans[0]['name'] ?? 'Starter',
                    'amount' => $amount, 'payment_status' => 'paid', 'paid_amount' => $amount,
                    'subscription_start' => Carbon::now()->toDateString(),
                    'subscription_end' => Carbon::now()->addYear()->toDateString(),
                ]);
                Commission::firstOrCreate(['sale_id' => $sale->id, 'user_id' => $exec->id], [
                    'type' => 'sale',
                    'amount' => round($amount * ($product->commission_value / 100), 2),
                    'status' => 'approved',
                ]);
            }
        }
    }

    private function makeUser(string $name, string $email, string $role, ?Branch $branch, ?Department $dept, string $designationName, ?int $managerId = null): User
    {
        $designation = $dept ? Designation::where('name', $designationName)->where('department_id', $dept->id)->first() : null;

        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'employee_code' => 'AK'.str_pad((string) (User::max('id') + 1), 4, '0', STR_PAD_LEFT),
            'password' => Hash::make('password'),
            'phone' => '9'.rand(100000000, 999999999),
            'branch_id' => $branch?->id,
            'department_id' => $dept?->id,
            'designation_id' => $designation?->id,
            'manager_id' => $managerId,
            'date_of_joining' => Carbon::now()->subMonths(rand(3, 24)),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$role]);

        EmployeeProfile::firstOrCreate(['user_id' => $user->id], [
            'gender' => 'male', 'blood_group' => 'O+',
            'basic_salary' => rand(15000, 40000),
            'mobile_allowance' => 500, 'petrol_allowance' => 2000,
            'emergency_contact_name' => 'Family', 'emergency_contact_phone' => '9'.rand(100000000, 999999999),
            'bank_name' => 'HDFC Bank', 'bank_ifsc' => 'HDFC0001234',
        ]);

        return $user;
    }
}
