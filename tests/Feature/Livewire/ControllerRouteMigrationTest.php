<?php

use App\Http\Controllers\TenantInvoiceDownloadController;
use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\PublicSite\ShowFaviconEndpoint;
use App\Livewire\Shell\LogoutEndpoint;
use App\Livewire\Shell\StopImpersonationEndpoint;
use App\Livewire\Tenant\InvoiceHistory;
use App\Livewire\Tenant\SubmitMeterReading;
use App\Livewire\Tenant\TenantDashboard;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Password;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders migrated public and nested tenant routes through the expected livewire components', function (Closure $requestFactory, string $component): void {
    $requestFactory($this)
        ->assertSuccessful()
        ->assertSeeLivewire($component);
})->with([
    'homepage' => [
        fn ($test) => $test->get(route('home')),
        HomepagePage::class,
    ],
    'login' => [
        fn ($test) => $test->get(route('login')),
        LoginPage::class,
    ],
    'register' => [
        fn ($test) => $test->get(route('register')),
        RegisterPage::class,
    ],
    'forgot password' => [
        fn ($test) => $test->get(route('password.request')),
        ForgotPasswordPage::class,
    ],
    'reset password' => [
        function ($test) {
            $user = User::factory()->create([
                'email' => 'reset-route@example.com',
            ]);

            $token = Password::broker()->createToken($user);

            return $test->get(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]));
        },
        ResetPasswordPage::class,
    ],
    'accept invitation' => [
        function ($test) {
            $invitation = OrganizationInvitation::factory()->create();

            return $test->get(route('invitation.show', $invitation->token));
        },
        AcceptInvitationPage::class,
    ],
    'welcome onboarding' => [
        function ($test) {
            $user = User::factory()->admin()->create([
                'organization_id' => null,
            ]);

            return $test->actingAs($user)->get(route('welcome.show'));
        },
        WelcomePage::class,
    ],
    'tenant home' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()->create();

            return $test->actingAs($tenant->user)->get(route('filament.admin.pages.tenant-dashboard'));
        },
        TenantDashboard::class,
    ],
    'tenant reading create' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()
                ->withAssignedProperty()
                ->withMeters(1)
                ->create();

            return $test->actingAs($tenant->user)->get(route('filament.admin.pages.tenant-submit-meter-reading'));
        },
        SubmitMeterReading::class,
    ],
    'tenant invoices' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()
                ->withAssignedProperty()
                ->withUnpaidInvoices(1)
                ->create();

            return $test->actingAs($tenant->user)->get(route('filament.admin.pages.tenant-invoice-history'));
        },
        InvoiceHistory::class,
    ],
]);

it('renders tenant property and profile routes without legacy livewire page wrappers', function (): void {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful();
});

it('keeps shared profile routing as a redirect into the appropriate destination', function (): void {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('profile.edit'))
        ->assertRedirect(route('filament.admin.pages.profile'));
});

it('maps non-livewire web routes to Livewire-backed actions', function (string $routeName, string $action): void {
    $route = app('router')->getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull()
        ->and($route->getActionName())->toBe($action);
})->with([
    'favicon' => ['favicon', ShowFaviconEndpoint::class.'@show'],
    'guest locale update' => ['locale.update', UpdateGuestLocaleEndpoint::class.'@update'],
    'login store' => ['login.store', LoginPage::class.'@store'],
    'register store' => ['register.store', RegisterPage::class.'@store'],
    'password email' => ['password.email', ForgotPasswordPage::class.'@sendResetLink'],
    'password update' => ['password.update', ResetPasswordPage::class.'@resetPassword'],
    'invitation store' => ['invitation.store', AcceptInvitationPage::class.'@store'],
    'welcome store' => ['welcome.store', WelcomePage::class.'@store'],
    'impersonation stop' => ['impersonation.stop', StopImpersonationEndpoint::class.'@stop'],
    'tenant invoice download' => ['tenant.invoices.download', TenantInvoiceDownloadController::class],
    'logout' => ['logout', LogoutEndpoint::class.'@logout'],
]);

it('keeps routes/web.php free of inline callbacks', function (): void {
    $contents = File::get(base_path('routes/web.php'));

    expect($contents)
        ->not->toContain('fn (')
        ->not->toContain('function (');
});
