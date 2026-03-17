<?php

use App\Actions\Auth\AcceptOrganizationInvitationAction;
use App\Actions\Auth\CompleteOnboardingAction;
use App\Actions\Auth\RegisterAdminAction;
use App\Actions\Preferences\ResolveGuestLocaleAction;
use App\Actions\Preferences\ResolveGuestLocaleRedirectAction;
use App\Actions\Preferences\StoreGuestLocaleAction;
use App\Actions\Tenant\Invoices\DownloadInvoiceAction;
use App\Actions\Tenant\Profile\UpdateTenantPasswordAction;
use App\Actions\Tenant\Profile\UpdateTenantProfileAction;
use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Http\Requests\Auth\AcceptInvitationRequest;
use App\Http\Requests\Auth\CompleteOnboardingRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Preferences\UpdateGuestLocaleRequest;
use App\Http\Requests\Tenant\UpdateTenantPasswordRequest;
use App\Http\Requests\Tenant\UpdateTenantProfileRequest;
use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Profile\EditProfilePage;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\Tenant\HomePage;
use App\Livewire\Tenant\InvoiceHistoryPage as TenantInvoiceHistoryPage;
use App\Livewire\Tenant\ProfilePage as TenantProfilePage;
use App\Livewire\Tenant\PropertyPage as TenantPropertyPage;
use App\Livewire\Tenant\ReadingCreatePage;
use App\Models\Invoice;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Support\Auth\AuthenticatedSessionHistory;
use App\Support\Auth\ImpersonationManager;
use App\Support\Auth\LoginRedirector;
use App\Support\Shell\DashboardUrlResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

Route::livewire('/', HomepagePage::class)->name('home');
Route::get('/favicon', fn () => response()->file(resource_path('icons/favicon.ico'), [
    'Cache-Control' => 'public, max-age=604800',
    'Content-Type' => 'image/x-icon',
]))->name('favicon');
Route::post('/locale', function (
    UpdateGuestLocaleRequest $request,
    ResolveGuestLocaleRedirectAction $resolveGuestLocaleRedirectAction,
    StoreGuestLocaleAction $storeGuestLocaleAction,
) {
    $storeGuestLocaleAction->handle($request, $request->locale());

    return redirect()->to($resolveGuestLocaleRedirectAction->handle($request));
})->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', LoginPage::class)->name('login');
    Route::post('/login', function (
        LoginRequest $request,
        LoginRedirector $loginRedirector,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ) {
        if (! Auth::attempt($request->credentials())) {
            throw ValidationException::withMessages([
                'email' => __('auth.invalid_credentials'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (
            $user->status === UserStatus::SUSPENDED ||
            $user->organization?->status === OrganizationStatus::SUSPENDED
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('auth.account_suspended'),
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()
            ->intended($loginRedirector->for($user))
            ->withCookie($authenticatedSessionHistory->remember());
    })->name('login.store');

    Route::livewire('/register', RegisterPage::class)->name('register');
    Route::post('/register', function (
        RegisterRequest $request,
        RegisterAdminAction $registerAdminAction,
    ) {
        $user = $registerAdminAction->handle($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('welcome.show');
    })->name('register.store');

    Route::livewire('/forgot-password', ForgotPasswordPage::class)->name('password.request');
    Route::post('/forgot-password', function (ForgotPasswordRequest $request) {
        Password::sendResetLink($request->validated());

        return back()->with('status', __('auth.reset_link_generic'));
    })->name('password.email');

    Route::livewire('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
    Route::post('/reset-password', function (ResetPasswordRequest $request) {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()
            ->withInput($request->safe()->except([
                'password',
                'password_confirmation',
            ]))
            ->withErrors([
                'email' => __($status),
            ]);
    })->name('password.update');

    Route::livewire('/invite/{token}', AcceptInvitationPage::class)->name('invitation.show');
    Route::post('/invite/{token}', function (
        AcceptInvitationRequest $request,
        string $token,
        AcceptOrganizationInvitationAction $acceptInvitation,
        ResolveGuestLocaleAction $resolveGuestLocaleAction,
        LoginRedirector $loginRedirector,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ) {
        $invitation = OrganizationInvitation::query()
            ->with(['organization', 'inviter'])
            ->where('token', $token)
            ->first();

        if (! $invitation?->isPending()) {
            return redirect()->route('invitation.show', $token);
        }

        $locale = $resolveGuestLocaleAction->handle($request);

        $user = $acceptInvitation->handle(
            $invitation,
            $request->validated(),
            $locale,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->intended($loginRedirector->for($user))
            ->withCookie($authenticatedSessionHistory->remember());
    })->name('invitation.store');
});

Route::middleware(['auth', 'set.auth.locale', 'ensure.account.accessible'])->group(function (): void {
    Route::livewire('/welcome', WelcomePage::class)->name('welcome.show');
    Route::post('/welcome', function (
        CompleteOnboardingRequest $request,
        CompleteOnboardingAction $completeOnboarding,
        LoginRedirector $loginRedirector,
    ) {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || filled($user->organization_id)) {
            return redirect()->to($loginRedirector->for($user));
        }

        $completeOnboarding->handle(
            $user,
            $request->validated(),
        );

        return redirect()->route('filament.admin.pages.organization-dashboard');
    })->name('welcome.store');
    Route::post('/impersonation/stop', function (
        Request $request,
        ImpersonationManager $impersonationManager,
        DashboardUrlResolver $dashboardUrlResolver,
    ) {
        $impersonator = $impersonationManager->resolveImpersonator($request);

        $impersonationManager->forget($request);

        if ($impersonator !== null) {
            Auth::guard('web')->login($impersonator);

            return redirect()->to($dashboardUrlResolver->for($impersonator));
        }

        return redirect()->to($dashboardUrlResolver->for($request->user()));
    })->name('impersonation.stop');
    Route::livewire('/profile', EditProfilePage::class)->name('profile.edit');

    Route::prefix('tenant')
        ->name('tenant.')
        ->middleware('tenant.only')
        ->group(function (): void {
            Route::livewire('/home', HomePage::class)->name('home');
            Route::livewire('/readings/create', ReadingCreatePage::class)->name('readings.create');
            Route::livewire('/invoices', TenantInvoiceHistoryPage::class)->name('invoices.index');
            Route::get('/invoices/{invoice}/download', function (
                Invoice $invoice,
                DownloadInvoiceAction $downloadInvoiceAction,
            ) {
                return $downloadInvoiceAction->handle($invoice);
            })->name('invoices.download');
            Route::livewire('/property', TenantPropertyPage::class)->name('property.show');
            Route::livewire('/profile', TenantProfilePage::class)->name('profile.edit');
            Route::put('/profile', function (
                UpdateTenantProfileRequest $request,
                UpdateTenantProfileAction $updateTenantProfileAction,
            ) {
                $updateTenantProfileAction->handle($request->user(), $request->validated());

                return to_route('tenant.profile.edit')->with('status', 'tenant-profile-updated');
            })->name('profile.update');
            Route::put('/profile/password', function (
                UpdateTenantPasswordRequest $request,
                UpdateTenantPasswordAction $updateTenantPasswordAction,
            ) {
                $updateTenantPasswordAction->handle($request->user(), $request->validated('password'));

                return to_route('tenant.profile.edit')->with('status', 'tenant-password-updated');
            })->name('profile.password.update');
        });
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', function (
        Request $request,
        AuthenticatedSessionHistory $authenticatedSessionHistory,
    ) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withCookie($authenticatedSessionHistory->forget());
    })->name('logout');
});
