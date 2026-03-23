<?php

use App\Http\Controllers\CspViolationReportController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\SwitchGuestLocaleController;
use App\Http\Controllers\TenantInvoiceDownloadController;
use App\Http\Controllers\TenantPortalRouteController;
use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\PublicSite\ShowFaviconEndpoint;
use App\Livewire\Shell\LogoutEndpoint;
use App\Livewire\Shell\StopImpersonationEndpoint;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepagePage::class)
    ->name('home');

Route::get('/favicon', [ShowFaviconEndpoint::class, 'show'])
    ->name('favicon');

Route::get('/favicon.ico', [ShowFaviconEndpoint::class, 'show']);

Route::post('/security/csp-report', CspViolationReportController::class)
    ->middleware('throttle:security-csp-report')
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('security.csp.report');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('/login', LoginPage::class)->middleware('guest')->name('login');
Route::post('/login', [LoginPage::class, 'store'])
    ->middleware(['guest', 'throttle:auth-login'])
    ->name('login.store');

Route::get('/forgot-password', ForgotPasswordPage::class)->middleware('guest')->name('password.request');
Route::post('/forgot-password', [ForgotPasswordPage::class, 'sendResetLink'])
    ->middleware(['guest', 'throttle:password-reset-link'])
    ->name('password.email');
Route::get('/reset-password/{token}', ResetPasswordPage::class)->middleware('guest')->name('password.reset');
Route::post('/reset-password', [ResetPasswordPage::class, 'resetPassword'])
    ->middleware(['guest', 'throttle:password-reset'])
    ->name('password.update');

Route::post('/logout', [LogoutEndpoint::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
Route::post('/impersonation/stop', [StopImpersonationEndpoint::class, 'stop'])
    ->middleware('auth')
    ->name('impersonation.stop');

Route::get('/register', RegisterPage::class)->middleware('guest')->name('register');
Route::post('/register', [RegisterPage::class, 'store'])
    ->middleware(['guest', 'throttle:auth-register'])
    ->name('register.store');

Route::get('/welcome', WelcomePage::class)->middleware('auth')->name('welcome.show');
Route::post('/welcome', [WelcomePage::class, 'store'])->middleware('auth')->name('welcome.store');
Route::get('/profile', EditProfilePage::class)->middleware('auth')->name('profile.edit');

Route::get('/language/{locale}', SwitchGuestLocaleController::class)->name('language.switch');

Route::post('/locale', [UpdateGuestLocaleEndpoint::class, 'update'])->name('locale.update');

Route::get('/tenant/invoices/{invoice}/download', TenantInvoiceDownloadController::class)
    ->middleware('auth')
    ->name('tenant.invoices.download');

Route::get('/tenant', TenantPortalRouteController::class)
    ->defaults('destination', 'home')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.home');
Route::get('/tenant/readings/create', TenantPortalRouteController::class)
    ->defaults('destination', 'readings.create')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.readings.create');
Route::get('/tenant/invoices', TenantPortalRouteController::class)
    ->defaults('destination', 'invoices.index')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.invoices.index');
Route::get('/tenant/property', TenantPortalRouteController::class)
    ->defaults('destination', 'property.show')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.property.show');
Route::get('/tenant/profile', TenantPortalRouteController::class)
    ->defaults('destination', 'profile.edit')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.profile.edit');

Route::get('/invitations/{token}/accept', AcceptInvitationPage::class)->name('invitation.show');
Route::post('/invitations/{token}/accept', [AcceptInvitationPage::class, 'store'])->name('invitation.store');
