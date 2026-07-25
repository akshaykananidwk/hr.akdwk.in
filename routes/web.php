<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UpdateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / PWA
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::get('/manifest.webmanifest', [SystemController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [SystemController::class, 'serviceWorker'])->name('pwa.sw');

/*
|--------------------------------------------------------------------------
| Installer (blocked once storage/installed lock exists)
|--------------------------------------------------------------------------
*/
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/finish', [InstallController::class, 'finish'])->name('finish');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Theme + locale (available to guests too)
Route::post('/theme', [SystemController::class, 'toggleTheme'])->name('theme.toggle');
Route::get('/locale/{locale}', [SystemController::class, 'switchLocale'])->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Authenticated application
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');

    // Daily reports
    Route::get('/reports', [DailyReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [DailyReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [DailyReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [DailyReportController::class, 'show'])->name('reports.show');

    // Leads / CRM
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{lead}/activity', [LeadController::class, 'addActivity'])->name('leads.activity');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Sales & commission
    Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('/commissions', [CommissionController::class, 'index'])->name('commissions.index');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');

    // Leave
    Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('/leaves/approvals', [LeaveController::class, 'approvals'])->name('leaves.approvals');
    Route::post('/leaves/{leave}/action', [LeaveController::class, 'action'])->name('leaves.action');

    // Employees (HR)
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/toggle', [EmployeeController::class, 'toggleActive'])->name('employees.toggle');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/documents', [ProfileController::class, 'uploadDocument'])->name('profile.documents');
    Route::post('/profile/accept-policy', [ProfileController::class, 'acceptPolicy'])->name('profile.accept-policy');

    // Admin: settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/policy', [SettingsController::class, 'updatePolicy'])->name('settings.policy');
    Route::post('/settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp');
    Route::post('/settings/whatsapp/test', [SettingsController::class, 'testWhatsapp'])->name('settings.whatsapp.test');

    // Admin: activity log
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');

    // Admin: legacy data import
    Route::get('/import', [\App\Http\Controllers\ImportController::class, 'index'])->name('import.index');
    Route::post('/import', [\App\Http\Controllers\ImportController::class, 'run'])->name('import.run');

    // Admin: smart auto-update
    Route::prefix('updates')->name('updates.')->group(function () {
        Route::get('/', [UpdateController::class, 'index'])->name('index');
        Route::post('/config', [UpdateController::class, 'saveConfig'])->name('config');
        Route::post('/check', [UpdateController::class, 'check'])->name('check');
        Route::post('/run', [UpdateController::class, 'update'])->name('run');
    });
});
