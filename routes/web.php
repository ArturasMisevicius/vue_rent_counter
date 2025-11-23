<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\OrganizationController as SuperadminOrganizationController;
use App\Http\Controllers\Superadmin\SubscriptionController as SuperadminSubscriptionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Admin\TariffController as AdminTariffController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Manager\ProfileController as ManagerProfileController;
use App\Http\Controllers\Manager\PropertyController as ManagerPropertyController;
use App\Http\Controllers\Manager\BuildingController as ManagerBuildingController;
use App\Http\Controllers\Manager\MeterController as ManagerMeterController;
use App\Http\Controllers\Manager\MeterReadingController as ManagerMeterReadingController;
use App\Http\Controllers\Manager\InvoiceController as ManagerInvoiceController;
use App\Http\Controllers\Manager\ReportController as ManagerReportController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\ProfileController as TenantProfileController;
use App\Http\Controllers\Tenant\InvoiceController as TenantInvoiceController;
use App\Http\Controllers\Tenant\PropertyController as TenantPropertyController;
use App\Http\Controllers\Tenant\MeterController as TenantMeterController;
use App\Http\Controllers\Tenant\MeterReadingController as TenantMeterReadingController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\ReportController;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

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
// ADMIN ROUTES
// ============================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Profile Management
    Route::get('profile', [AdminProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
    
    // Tenant Management
    Route::get('tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('tenants/create', [AdminTenantController::class, 'create'])->name('tenants.create');
    Route::post('tenants', [AdminTenantController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{user}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('tenants/{user}/edit', [AdminTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('tenants/{user}', [AdminTenantController::class, 'update'])->name('tenants.update');
    Route::delete('tenants/{user}', [AdminTenantController::class, 'destroy'])->name('tenants.destroy');
    Route::get('tenants/{user}/reassign', [AdminTenantController::class, 'showReassignForm'])->name('tenants.reassign');
    Route::post('tenants/{user}/reassign', [AdminTenantController::class, 'reassign'])->name('tenants.reassign.store');
    Route::post('tenants/{user}/deactivate', [AdminTenantController::class, 'deactivate'])->name('tenants.deactivate');
    Route::post('tenants/{user}/reactivate', [AdminTenantController::class, 'reactivate'])->name('tenants.reactivate');
    
    // User Management
    Route::resource('users', AdminUserController::class);
    Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    
    // Provider Management
    Route::resource('providers', AdminProviderController::class);
    
    // Tariff Management
    Route::resource('tariffs', AdminTariffController::class);
    Route::get('tariffs/{tariff}/history', [AdminTariffController::class, 'history'])->name('tariffs.history');
    Route::post('tariffs/{tariff}/duplicate', [AdminTariffController::class, 'duplicate'])->name('tariffs.duplicate');
    
    // Audit Trail
    Route::get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
    Route::get('audit/{audit}', [AdminAuditController::class, 'show'])->name('audit.show');
    Route::get('audit/meter-readings', [AdminAuditController::class, 'meterReadings'])->name('audit.meter-readings');
    Route::get('audit/invoices', [AdminAuditController::class, 'invoices'])->name('audit.invoices');
    Route::get('audit/users', [AdminAuditController::class, 'users'])->name('audit.users');
    
    // System Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/run-backup', [AdminSettingsController::class, 'runBackup'])->name('settings.run-backup');
    Route::post('settings/clear-cache', [AdminSettingsController::class, 'clearCache'])->name('settings.clear-cache');
});

// ============================================================================
// MANAGER ROUTES
// ============================================================================
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    
    // Profile
    Route::get('profile', [ManagerProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ManagerProfileController::class, 'update'])->name('profile.update');
    
    // Properties
    Route::resource('properties', ManagerPropertyController::class);
    
    // Buildings
    Route::resource('buildings', ManagerBuildingController::class);
    Route::post('buildings/{building}/calculate-gyvatukas', [ManagerBuildingController::class, 'calculateGyvatukas'])->name('buildings.calculate-gyvatukas');
    
    // Meters
    Route::resource('meters', ManagerMeterController::class);
    
    // Meter Readings
    Route::resource('meter-readings', ManagerMeterReadingController::class);
    
    // Invoices
    Route::get('invoices/drafts', [ManagerInvoiceController::class, 'drafts'])->name('invoices.drafts');
    Route::resource('invoices', ManagerInvoiceController::class);
    Route::post('invoices/{invoice}/finalize', [ManagerInvoiceController::class, 'finalize'])->name('invoices.finalize');
    
    // Reports
    Route::get('reports', [ManagerReportController::class, 'index'])->name('reports.index');
    Route::get('reports/consumption', [ManagerReportController::class, 'consumption'])->name('reports.consumption');
    Route::get('reports/revenue', [ManagerReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/meter-reading-compliance', [ManagerReportController::class, 'meterReadingCompliance'])->name('reports.meter-reading-compliance');
    
    // Providers (Read-only)
    Route::get('providers', [AdminProviderController::class, 'index'])->name('providers.index');
    Route::get('providers/{provider}', [AdminProviderController::class, 'show'])->name('providers.show');
    
    // Tariffs (Read-only)
    Route::get('tariffs', [AdminTariffController::class, 'index'])->name('tariffs.index');
    Route::get('tariffs/{tariff}', [AdminTariffController::class, 'show'])->name('tariffs.show');
    
    // Audit (Limited)
    Route::get('audit/meter-readings', [AdminAuditController::class, 'meterReadings'])->name('audit.meter-readings');
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
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    
    // Buildings
    Route::resource('buildings', BuildingController::class);
    Route::post('buildings/{building}/calculate-gyvatukas', [BuildingController::class, 'calculateGyvatukas'])->name('buildings.calculate-gyvatukas');
    Route::get('buildings/{building}/properties', [BuildingController::class, 'properties'])->name('buildings.properties');
    
    // Properties
    Route::resource('properties', PropertyController::class);
    Route::get('properties/{property}/meters', [PropertyController::class, 'meters'])->name('properties.meters');
    Route::get('properties/{property}/tenants', [PropertyController::class, 'tenants'])->name('properties.tenants');
    Route::get('properties/{property}/invoices', [PropertyController::class, 'invoices'])->name('properties.invoices');
    
    // Tenants
    Route::resource('tenants', TenantController::class);
    Route::get('tenants/{tenant}/invoices', [TenantController::class, 'invoices'])->name('tenants.invoices');
    Route::get('tenants/{tenant}/consumption', [TenantController::class, 'consumption'])->name('tenants.consumption');
    Route::post('tenants/{tenant}/send-invoice', [TenantController::class, 'sendInvoice'])->name('tenants.send-invoice');
    
    // Meters
    Route::resource('meters', MeterController::class);
    Route::get('meters/{meter}/readings', [MeterController::class, 'readings'])->name('meters.readings');
    Route::get('meters/pending-readings', [MeterController::class, 'pendingReadings'])->name('meters.pending-readings');
    
    // Meter Readings
    Route::resource('meter-readings', MeterReadingController::class);
    Route::get('meter-readings/{meterReading}/audit', [MeterReadingController::class, 'audit'])->name('meter-readings.audit');
    Route::post('meter-readings/bulk', [MeterReadingController::class, 'bulk'])->name('meter-readings.bulk');
    Route::get('meter-readings/export', [MeterReadingController::class, 'export'])->name('meter-readings.export');
    
    // Invoices
    // Invoice specific routes (must come before resource routes)
    Route::get('invoices/drafts', [InvoiceController::class, 'drafts'])->name('invoices.drafts');
    Route::get('invoices/finalized', [InvoiceController::class, 'finalized'])->name('invoices.finalized');
    Route::get('invoices/paid', [InvoiceController::class, 'paid'])->name('invoices.paid');
    Route::post('invoices/generate-bulk', [InvoiceController::class, 'generateBulk'])->name('invoices.generate-bulk');
    
    // Invoice resource routes
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    
    // Invoice Items
    Route::get('invoices/{invoice}/items', [InvoiceItemController::class, 'index'])->name('invoices.items.index');
    Route::post('invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoices.items.store');
    Route::get('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'show'])->name('invoices.items.show');
    Route::put('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'update'])->name('invoices.items.update');
    Route::delete('invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'destroy'])->name('invoices.items.destroy');
    
    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/consumption', [ReportController::class, 'consumption'])->name('reports.consumption');
    Route::get('reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
    Route::get('reports/meter-readings', [ReportController::class, 'meterReadings'])->name('reports.meter-readings');
    Route::get('reports/gyvatukas', [ReportController::class, 'gyvatukas'])->name('reports.gyvatukas');
    Route::get('reports/tariff-comparison', [ReportController::class, 'tariffComparison'])->name('reports.tariff-comparison');
    Route::post('reports/export', [ReportController::class, 'export'])->name('reports.export');
});
