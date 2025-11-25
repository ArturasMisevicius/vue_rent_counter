<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Admin\TariffController as AdminTariffController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\OrganizationController as SuperadminOrganizationController;
use App\Http\Controllers\Superadmin\SubscriptionController as SuperadminSubscriptionController;
use App\Http\Controllers\Superadmin\ProfileController as SuperadminProfileController;
use App\Http\Controllers\Manager\BuildingController as ManagerBuildingController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Manager\InvoiceController as ManagerInvoiceController;
use App\Http\Controllers\Manager\MeterController as ManagerMeterController;
use App\Http\Controllers\Manager\MeterReadingController as ManagerMeterReadingController;
use App\Http\Controllers\Manager\ProfileController as ManagerProfileController;
use App\Http\Controllers\Manager\PropertyController as ManagerPropertyController;
use App\Http\Controllers\Manager\ReportController as ManagerReportController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\ProfileController as TenantProfileController;
use App\Http\Controllers\Tenant\InvoiceController as TenantInvoiceController;
use App\Http\Controllers\Tenant\PropertyController as TenantPropertyController;
use App\Http\Controllers\Tenant\MeterController as TenantMeterController;
use App\Http\Controllers\Tenant\MeterReadingController as TenantMeterReadingController;
use App\Http\Controllers\MeterReadingUpdateController;
use App\Http\Controllers\FinalizeInvoiceController;
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
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Allow re-authentication even when already logged in (used in workflow tests)
Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ============================================================================
// SUPERADMIN ROUTES
// ============================================================================
Route::middleware(['auth', 'role:superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [SuperadminDashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('profile', [SuperadminProfileController::class, 'show'])->name('profile.show');
    Route::match(['put', 'patch'], 'profile', [SuperadminProfileController::class, 'update'])->name('profile.update');
    
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
// ADMIN ROUTES
// ============================================================================
// Admin routes for custom admin interface (non-Filament)
// Filament Resources are also available at /admin for Properties, Buildings, 
// Meters, MeterReadings, Invoices, and Subscriptions management.

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('profile', [AdminProfileController::class, 'show'])->name('profile.show');
    Route::match(['put', 'patch'], 'profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::match(['put', 'patch'], 'profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // User Management
    Route::resource('users', AdminUserController::class);
    
    // Provider Management
    Route::resource('providers', AdminProviderController::class);
    
    // Tariff Management
    Route::resource('tariffs', AdminTariffController::class);
    
    // Tenant Management (Admin-specific tenant views)
    Route::get('tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('tenants/create', [AdminTenantController::class, 'create'])->name('tenants.create');
    Route::post('tenants', [AdminTenantController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('tenants/{tenant}/reassign', [AdminTenantController::class, 'reassign'])->name('tenants.reassign');
    Route::post('tenants/{tenant}/reassign', [AdminTenantController::class, 'processReassignment'])->name('tenants.process-reassignment');
    Route::delete('tenants/{tenant}', [AdminTenantController::class, 'destroy'])->name('tenants.destroy');
    
    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/backup', [AdminSettingsController::class, 'runBackup'])->name('settings.backup');
    Route::post('settings/cache', [AdminSettingsController::class, 'clearCache'])->name('settings.cache');
    
    // Audit Log
    Route::get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
});

// Filament route aliases pointing to existing admin user screens to satisfy navigation links
Route::middleware(['auth', 'role:admin'])->prefix('admin/filament')->group(function () {
    Route::get('users', [AdminUserController::class, 'index'])->name('filament.admin.resources.users.index');
    Route::get('users/create', [AdminUserController::class, 'create'])->name('filament.admin.resources.users.create');
    Route::get('providers', [AdminProviderController::class, 'index'])->name('filament.admin.resources.providers.index');
    Route::get('providers/create', [AdminProviderController::class, 'create'])->name('filament.admin.resources.providers.create');
    Route::get('tariffs', [AdminTariffController::class, 'index'])->name('filament.admin.resources.tariffs.index');
    Route::get('tariffs/create', [AdminTariffController::class, 'create'])->name('filament.admin.resources.tariffs.create');
});

// ============================================================================
// MANAGER ROUTES
// ============================================================================
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    // Dashboard - Custom manager overview
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');

    // Profile & preferences
    Route::get('profile', [ManagerProfileController::class, 'show'])->name('profile.show');
    Route::match(['put', 'patch'], 'profile', [ManagerProfileController::class, 'update'])->name('profile.update');
    
    // Reports - Manager-specific reporting (not in Filament)
    Route::get('reports', [ManagerReportController::class, 'index'])->name('reports.index');
    Route::get('reports/consumption', [ManagerReportController::class, 'consumption'])->name('reports.consumption');
    Route::get('reports/consumption/export', [ManagerReportController::class, 'exportConsumption'])->name('reports.consumption.export');
    Route::get('reports/revenue', [ManagerReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/revenue/export', [ManagerReportController::class, 'exportRevenue'])->name('reports.revenue.export');
    Route::get('reports/meter-reading-compliance', [ManagerReportController::class, 'meterReadingCompliance'])->name('reports.meter-reading-compliance');
    Route::get('reports/meter-reading-compliance/export', [ManagerReportController::class, 'exportCompliance'])->name('reports.compliance.export');

    // Resource management (manager-facing UI)
    Route::resource('properties', ManagerPropertyController::class);
    Route::resource('buildings', ManagerBuildingController::class);
    Route::resource('meters', ManagerMeterController::class);

    // Meter readings (manager-facing UI)
    Route::resource('meter-readings', ManagerMeterReadingController::class);
    
    // Meter reading corrections (single-action controller for updates)
    // Requirements: 1.1, 1.2, 1.3, 1.4, 8.1, 8.2, 8.3
    Route::put('meter-readings/{meterReading}/correct', MeterReadingUpdateController::class)
        ->name('meter-readings.correct');

    // Invoices (manager-facing UI)
    Route::get('invoices/drafts', [ManagerInvoiceController::class, 'drafts'])->name('invoices.drafts');
    Route::get('invoices/finalized', [ManagerInvoiceController::class, 'finalized'])->name('invoices.finalized');
    
    // Invoice finalization (single-action controller)
    // Requirements: 5.5, 11.1, 11.3
    Route::post('invoices/{invoice}/finalize', FinalizeInvoiceController::class)->name('invoices.finalize');
    
    Route::post('invoices/{invoice}/mark-paid', [ManagerInvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::resource('invoices', ManagerInvoiceController::class);
    
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
    Route::post('meter-readings', [TenantMeterReadingController::class, 'store'])->name('meter-readings.store');
    
    // Invoices (Own)
    Route::get('invoices', [TenantInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [TenantInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [TenantInvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/receipt', [TenantInvoiceController::class, 'pdf'])->name('invoices.receipt');
});

// ============================================================================
// SHARED ROUTES (ADMIN & MANAGER)
// ============================================================================
// Note: Most admin/manager functionality is now handled through Filament at /admin
// This includes: Buildings, Properties, Meters, MeterReadings, Invoices, 
// Tariffs, Providers, and Users management
