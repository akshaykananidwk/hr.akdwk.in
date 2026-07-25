<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstallController extends Controller
{
    /** Requirements the installer verifies before continuing. */
    private array $requiredExtensions = [
        'pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'fileinfo',
    ];

    public function index()
    {
        if (app_installed()) {
            return redirect()->route('login')->with('error', 'Application is already installed.');
        }

        return view('install.requirements', [
            'phpOk' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'phpVersion' => PHP_VERSION,
            'extensions' => collect($this->requiredExtensions)->mapWithKeys(
                fn ($ext) => [$ext => extension_loaded($ext)]
            ),
            'writable' => [
                'storage/' => is_writable(storage_path()),
                'bootstrap/cache/' => is_writable(base_path('bootstrap/cache')),
                '.env' => is_writable(base_path('.env')) || is_writable(base_path()),
            ],
        ]);
    }

    public function database()
    {
        if (app_installed()) {
            return redirect()->route('login');
        }

        return view('install.database');
    }

    public function saveDatabase(Request $request)
    {
        $data = $request->validate([
            'db_connection' => ['required', 'in:mysql,sqlite,pgsql'],
            'db_host' => ['nullable', 'string'],
            'db_port' => ['nullable', 'string'],
            'db_database' => ['required', 'string'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        // Test the connection before persisting it.
        try {
            if ($data['db_connection'] === 'sqlite') {
                $path = $data['db_database'];
                if (! Str::startsWith($path, '/')) {
                    $path = database_path($path);
                }
                if (! file_exists($path)) {
                    touch($path);
                }
                $data['db_database'] = $path;
                new \PDO('sqlite:'.$path);
            } else {
                $driver = $data['db_connection'];
                $host = $data['db_host'] ?: '127.0.0.1';
                $port = $data['db_port'] ?: ($driver === 'pgsql' ? '5432' : '3306');
                $dsn = "{$driver}:host={$host};port={$port};dbname={$data['db_database']}";
                new \PDO($dsn, $data['db_username'] ?: '', $data['db_password'] ?: '');
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Database connection failed: '.$e->getMessage());
        }

        $this->writeEnv([
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'] ?: '127.0.0.1',
            'DB_PORT' => $data['db_port'] ?: ($data['db_connection'] === 'pgsql' ? '5432' : '3306'),
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'] ?: '',
            'DB_PASSWORD' => $data['db_password'] ?: '',
        ]);

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        if (app_installed()) {
            return redirect()->route('login');
        }

        return view('install.admin');
    }

    public function finish(Request $request)
    {
        if (app_installed()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'app_url' => ['nullable', 'url'],
            'seed_demo' => ['nullable', 'boolean'],
        ]);

        @set_time_limit(300);

        try {
            // Persist app url if provided.
            if (! empty($data['app_url'])) {
                $this->writeEnv(['APP_URL' => $data['app_url']]);
            }

            Artisan::call('config:clear');

            // Build the schema.
            Artisan::call('migrate', ['--force' => true]);

            // Essential seeders (roles, settings, organization).
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\SettingsSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\OrganizationSeeder', '--force' => true]);

            // Store company name.
            Setting::put('company_name', $data['company_name'], 'company');

            // Create the Super Admin.
            $admin = User::firstOrCreate(['email' => $data['admin_email']], [
                'name' => $data['admin_name'],
                'employee_code' => 'AK0001',
                'password' => Hash::make($data['admin_password']),
                'branch_id' => Branch::first()?->id,
                'department_id' => Department::where('name', 'Administration')->first()?->id,
                'date_of_joining' => now(),
                'status' => 'active',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $admin->syncRoles(['Super Admin']);
            EmployeeProfile::firstOrCreate(['user_id' => $admin->id]);

            if (! empty($data['seed_demo'])) {
                Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
            }

            // Optimise + lock.
            Artisan::call('storage:link', []);
            Artisan::call('config:clear');
            file_put_contents(storage_path('installed'), 'installed at '.now()->toDateTimeString().' v'.Setting::get('app_version', '1.0.0'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Installation failed: '.$e->getMessage());
        }

        return view('install.done', ['email' => $data['admin_email']]);
    }

    /**
     * Update key=value pairs inside the .env file without touching the rest.
     */
    private function writeEnv(array $values): void
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            copy(base_path('.env.example'), $path);
        }
        $content = file_get_contents($path);

        foreach ($values as $key => $value) {
            // Quote values that contain spaces or are empty.
            $escaped = (str_contains((string) $value, ' ') || $value === '') ? '"'.$value.'"' : $value;
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= PHP_EOL."{$key}={$escaped}";
            }
        }

        file_put_contents($path, $content);
    }
}
