<?php

use App\Http\Controllers\NotificationTrackingController;
use App\Http\Controllers\TenantInvoiceDownloadController;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\Shell\StopImpersonationEndpoint;
use Illuminate\Support\Facades\Route;

Route::livewire('/welcome', WelcomePage::class)->name('welcome.show');
Route::post('/welcome', [WelcomePage::class, 'store'])->name('welcome.store');
Route::post('/impersonation/stop', [StopImpersonationEndpoint::class, 'stop'])->name('impersonation.stop');
Route::post('/notification-track/{platformNotificationRecipient}', NotificationTrackingController::class)->name('notifications.track');
Route::livewire('/profile', EditProfilePage::class)->name('profile.edit');
Route::get('/tenant/invoices/{invoice}/download', TenantInvoiceDownloadController::class)
    ->middleware('tenant.only')
    ->name('tenant.invoices.download');
