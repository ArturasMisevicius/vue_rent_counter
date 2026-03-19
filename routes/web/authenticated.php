<?php

use App\Http\Controllers\NotificationTrackingController;
use App\Http\Controllers\TenantInvoiceDownloadController;
use App\Http\Controllers\TenantPortalRouteController;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\Shell\StopImpersonationEndpoint;
use Illuminate\Support\Facades\Route;

Route::livewire('/welcome', WelcomePage::class)->name('welcome.show');
Route::post('/welcome', [WelcomePage::class, 'store'])->name('welcome.store');
Route::post('/impersonation/stop', [StopImpersonationEndpoint::class, 'stop'])->name('impersonation.stop');
Route::post('/notification-track/{platformNotificationRecipient}', NotificationTrackingController::class)->name('notifications.track');
Route::livewire('/profile', EditProfilePage::class)->name('profile.edit');

Route::prefix('tenant')
    ->name('tenant.')
    ->middleware('tenant.only')
    ->group(function (): void {
        Route::get('/home', TenantPortalRouteController::class)
            ->defaults('destination', 'home')
            ->name('home');

        Route::get('/readings/create', TenantPortalRouteController::class)
            ->defaults('destination', 'readings.create')
            ->name('readings.create');

        Route::get('/invoices', TenantPortalRouteController::class)
            ->defaults('destination', 'invoices.index')
            ->name('invoices.index');

        Route::get('/property', TenantPortalRouteController::class)
            ->defaults('destination', 'property.show')
            ->name('property.show');

        Route::get('/profile', TenantPortalRouteController::class)
            ->defaults('destination', 'profile.edit')
            ->name('profile.edit');

        Route::get('/invoices/{invoice}/download', TenantInvoiceDownloadController::class)
            ->name('invoices.download');
    });
