<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the app is considered "installed" so the guard middleware passes.
        if (! file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), 'test');
        }

        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class, OrganizationSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['password' => Hash::make('password'), 'is_active' => true]);
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign In');
    }

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['login' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_staff_can_login_with_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '9876543210',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);
        $user->assignRole('Employee');

        $this->post('/login', ['login' => '9876543210', 'password' => 'secret123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_roles_and_permissions_are_seeded(): void
    {
        $this->assertDatabaseCount('roles', 9);
        $this->assertTrue($this->admin()->can('manage settings'));
    }

    public function test_sales_executive_cannot_manage_settings(): void
    {
        $exec = User::factory()->create(['is_active' => true]);
        $exec->assignRole('Sales Executive');

        $this->actingAs($exec)->get('/settings')->assertForbidden();
    }

    public function test_admin_can_create_a_lead(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/leads', [
            'name' => 'Test Owner',
            'business_name' => 'Test Biz',
            'phone' => '9999999999',
            'source' => 'manual',
            'status' => 'new',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['business_name' => 'Test Biz']);
    }

    public function test_winning_a_lead_creates_a_sale_and_commission(): void
    {
        $admin = $this->admin();
        $product = Product::first();
        $lead = Lead::create([
            'name' => 'Won Owner', 'business_name' => 'Won Biz', 'source' => 'manual',
            'status' => 'negotiation', 'product_id' => $product->id, 'assigned_to' => $admin->id,
            'expected_value' => 5000,
        ]);

        $this->actingAs($admin)->post("/leads/{$lead->id}/status", ['status' => 'won'])->assertRedirect();

        $this->assertDatabaseHas('sales', ['lead_id' => $lead->id]);
        $this->assertDatabaseHas('commissions', ['user_id' => $admin->id]);
    }

    public function test_uninstalled_app_forces_the_installer(): void
    {
        @unlink(storage_path('installed'));

        // Public entry points funnel to the installer until the lock exists.
        $this->get('/login')->assertRedirect('/install');
        $this->get('/install')->assertOk();

        // restore for other tests
        file_put_contents(storage_path('installed'), 'test');
    }
}
