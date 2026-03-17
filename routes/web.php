<?php

use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\StopImpersonationController;
use App\Http\Controllers\Onboarding\WelcomeController;
use App\Http\Controllers\Preferences\UpdateGuestLocaleController;
use App\Http\Controllers\Profile\EditProfileController;
use App\Http\Controllers\PublicSite\FaviconController;
use App\Http\Controllers\PublicSite\HomepageController;
use App\Http\Controllers\Tenant\HomeController;
use App\Http\Controllers\Tenant\Invoices\DownloadController as TenantInvoiceDownloadController;
use App\Http\Controllers\Tenant\Invoices\IndexController as TenantInvoiceIndexController;
use App\Http\Controllers\Tenant\Profile\EditController as TenantProfileEditController;
use App\Http\Controllers\Tenant\Profile\UpdateController as TenantProfileUpdateController;
use App\Http\Controllers\Tenant\Profile\UpdatePasswordController as TenantProfileUpdatePasswordController;
use App\Http\Controllers\Tenant\Property\ShowController as TenantPropertyShowController;
use App\Http\Controllers\Tenant\Readings\CreateController as TenantReadingCreateController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepageController::class)->name('home');
Route::get('/favicon', FaviconController::class)->name('favicon');
Route::post('/locale', UpdateGuestLocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

    Route::get('/invite/{token}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invite/{token}', [AcceptInvitationController::class, 'store'])->name('invitation.store');
});

Route::middleware(['auth', 'set.auth.locale', 'ensure.account.accessible'])->group(function (): void {
    Route::get('/welcome', [WelcomeController::class, 'show'])->name('welcome.show');
    Route::post('/welcome', [WelcomeController::class, 'store'])->name('welcome.store');
    Route::post('/impersonation/stop', StopImpersonationController::class)->name('impersonation.stop');
    Route::get('/profile', EditProfileController::class)->name('profile.edit');

    Route::prefix('tenant')
        ->name('tenant.')
        ->middleware('tenant.only')
        ->group(function (): void {
            Route::get('/home', HomeController::class)->name('home');
            Route::get('/readings/create', TenantReadingCreateController::class)->name('readings.create');
            Route::get('/invoices', TenantInvoiceIndexController::class)->name('invoices.index');
            Route::get('/invoices/{invoice}/download', TenantInvoiceDownloadController::class)->name('invoices.download');
            Route::get('/property', TenantPropertyShowController::class)->name('property.show');
            Route::get('/profile', TenantProfileEditController::class)->name('profile.edit');
            Route::put('/profile', TenantProfileUpdateController::class)->name('profile.update');
            Route::put('/profile/password', TenantProfileUpdatePasswordController::class)->name('profile.password.update');
        });
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
});
