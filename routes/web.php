<?php

use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Kyc\ShowKycAttachmentEndpoint;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Preferences\SwitchGuestLocaleEndpoint;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\Profile\ShowProfileAvatarEndpoint;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\PublicSite\ShowFaviconEndpoint;
use App\Livewire\Security\CspViolationReportEndpoint;
use App\Livewire\Shell\DashboardRedirectEndpoint;
use App\Livewire\Shell\LogoutEndpoint;
use App\Livewire\Shell\StopImpersonationEndpoint;
use App\Livewire\Superadmin\ExportRecentOrganizationsCsvEndpoint;
use App\Livewire\Tenant\DownloadInvoiceEndpoint;
use App\Livewire\Tenant\TenantPortalRouteEndpoint;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepagePage::class)
    ->name('home');

Route::get('/favicon', [ShowFaviconEndpoint::class, 'show'])
    ->name('favicon');

Route::get('/favicon.ico', [ShowFaviconEndpoint::class, 'show']);

Route::post('/security/csp-report', [CspViolationReportEndpoint::class, 'store'])
    ->middleware('throttle:security-csp-report')
    ->withoutMiddleware([PreventRequestForgery::class])
    ->name('security.csp.report');

Route::get('/dashboard', [DashboardRedirectEndpoint::class, 'show'])
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
Route::get('/profile/avatar', [ShowProfileAvatarEndpoint::class, 'show'])
    ->middleware('auth')
    ->name('profile.avatar.show');

Route::middleware('auth')
    ->prefix('app/platform-dashboard')
    ->name('filament.admin.pages.platform-dashboard.')
    ->group(function (): void {
        Route::get('/recent-organizations-export', [ExportRecentOrganizationsCsvEndpoint::class, 'download'])
            ->name('recent-organizations-export');
    });

Route::get('/language/{locale}', [SwitchGuestLocaleEndpoint::class, 'change'])->name('language.switch');

Route::post('/locale', [UpdateGuestLocaleEndpoint::class, 'update'])->name('locale.update');

Route::get('/tenant/invoices/{invoice}/download', [DownloadInvoiceEndpoint::class, 'download'])
    ->middleware('auth')
    ->name('tenant.invoices.download');

Route::get('/kyc/attachments/{attachment}', [ShowKycAttachmentEndpoint::class, 'show'])
    ->middleware('auth')
    ->name('kyc.attachments.show');

Route::get('/tenant', [TenantPortalRouteEndpoint::class, 'show'])
    ->defaults('destination', 'home')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.home');
Route::get('/tenant/readings/create', [TenantPortalRouteEndpoint::class, 'show'])
    ->defaults('destination', 'readings.create')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.readings.create');
Route::get('/tenant/invoices', [TenantPortalRouteEndpoint::class, 'show'])
    ->defaults('destination', 'invoices.index')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.invoices.index');
Route::get('/tenant/property', [TenantPortalRouteEndpoint::class, 'show'])
    ->defaults('destination', 'property.show')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.property.show');
Route::get('/tenant/profile', [TenantPortalRouteEndpoint::class, 'show'])
    ->defaults('destination', 'profile.edit')
    ->middleware(['auth', 'tenant.only'])
    ->name('tenant.profile.edit');

Route::get('/invitations/{token}/accept', AcceptInvitationPage::class)->name('invitation.show');
Route::post('/invitations/{token}/accept', [AcceptInvitationPage::class, 'store'])->name('invitation.store');
