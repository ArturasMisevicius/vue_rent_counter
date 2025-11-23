<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\OrganizationController as SuperadminOrganizationController;
use App\Http\Controllers\Superadmin\SubscriptionController as SuperadminSubscriptionController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Manager\ReportController as ManagerReportController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\ProfileController as TenantProfileController;
use App\Http\Controllers\Tenant\InvoiceController as TenantInvoiceController;
use App\Http\Controllers\Tenant\PropertyController as TenantPropertyController;
use App\Http\Controllers\Tenant\MeterController as TenantMeterController;
use App\Http\Controllers\Tenant\MeterReadingController as TenantMeterReadingController;
use App\Http\Controllers\LocaleController;

// Public routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return app(\App\Http\Controllers\WelcomeController::class)();
});

// Unified dashboard route - redirects based on user role
Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    return match ($user->role->value) {
        'superadmin' => redirect()->route('superadmin.dashboard'),
        'admin' => redirect('/admin'), // Filament panel
        'manager' => redirect()->route('manager.dashboard'),
        'tenant' => redirect()->route('tenant.dashboard'),
        default => abort(403, 'Invalid user role'),
    };
})->middleware('auth')->name('dashboard');

Route::post('/locale', [LocaleController::class, 'store'])
    ->middleware('web')
    ->name('locale.set');

// Debug route to test if routing works
Route::get('/test-debug', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel is working',
        'timestamp' => now()->toDateTimeString(),
    ]);
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ============================================================================
// SUPERADMIN ROUTES
// ============================================================================
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [SuperadminDashboardController::class, 'index'])->name('dashboard');
    
    // Organization Management
    Route::get('organizations', [SuperadminOrganizationController::class, 'index'])->name('organizations.index');
    Route::get('organizations/create', [SuperadminOrganizationController::class, 'create'])->name('organizations.create');
    Route::post('organizations', [SuperadminOrganizationController::class, 'store'])->name('organizations.store');
    Route::get('organizations/{user}', [SuperadminOrganizationController::class, 'show'])->name('organizations.show');
    Route::get('organizations/{user}/edit', [SuperadminOrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('organizations/{user}', [SuperadminOrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('organizations/{user}', [SuperadminOrganizationController::class, 'destroy'])->name('organizations.destroy');
    
    // Subscription Management
    Route::get('subscriptions', [SuperadminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/{subscription}', [SuperadminSubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::get('subscriptions/{subscription}/edit', [SuperadminSubscriptionController::class, 'edit'])->name('subscriptions.edit');
    Route::put('subscriptions/{subscription}', [SuperadminSubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::post('subscriptions/{subscription}/renew', [SuperadminSubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/suspend', [SuperadminSubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
    Route::post('subscriptions/{subscription}/cancel', [SuperadminSubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
});

// ============================================================================
// ADMIN ROUTES - Handled by Filament Panel at /admin
// ============================================================================
// All admin functionality (Properties, Buildings, Meters, MeterReadings, 
// Invoices, Tariffs, Providers, Users, Subscriptions) is managed through 
// Filament Resources. Access via /admin with admin or manager role.

// ============================================================================
// MANAGER ROUTES
// ============================================================================
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    // Dashboard - Custom manager overview
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    
    // Reports - Manager-specific reporting (not in Filament)
    Route::get('reports', [ManagerReportController::class, 'index'])->name('reports.index');
    Route::get('reports/consumption', [ManagerReportController::class, 'consumption'])->name('reports.consumption');
    Route::get('reports/revenue', [ManagerReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/meter-reading-compliance', [ManagerReportController::class, 'meterReadingCompliance'])->name('reports.meter-reading-compliance');
    
    // Note: Properties, Buildings, Meters, MeterReadings, Invoices, Tariffs, 
    // and Providers are managed through Filament at /admin
});

// ============================================================================
// TENANT ROUTES
// ============================================================================
Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('profile', [TenantProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [TenantProfileController::class, 'update'])->name('profile.update');
    
    // Property (Own)
    Route::get('property', [TenantPropertyController::class, 'show'])->name('property.show');
    Route::get('property/meters', [TenantPropertyController::class, 'meters'])->name('property.meters');
    
    // Meters (Own)
    Route::get('meters', [TenantMeterController::class, 'index'])->name('meters.index');
    Route::get('meters/{meter}', [TenantMeterController::class, 'show'])->name('meters.show');
    
    // Meter Readings (Own)
    Route::get('meter-readings', [TenantMeterReadingController::class, 'index'])->name('meter-readings.index');
    Route::get('meter-readings/{meterReading}', [TenantMeterReadingController::class, 'show'])->name('meter-readings.show');
    
    // Invoices (Own)
    Route::get('invoices', [TenantInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [TenantInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [TenantInvoiceController::class, 'pdf'])->name('invoices.pdf');
});

// ============================================================================
// SHARED ROUTES (ADMIN & MANAGER)
// ============================================================================
// Note: Most admin/manager functionality is now handled through Filament at /admin
// This includes: Buildings, Properties, Meters, MeterReadings, Invoices, 
// Tariffs, Providers, and Users management
