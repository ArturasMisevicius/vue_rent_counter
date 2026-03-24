<?php

use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\Preferences\SwitchGuestLocaleEndpoint;
use App\Livewire\Preferences\UpdateGuestLocaleEndpoint;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\PublicSite\ShowFaviconEndpoint;
use App\Livewire\Security\CspViolationReportEndpoint;
use App\Livewire\Shell\DashboardRedirectEndpoint;
use App\Livewire\Shell\LogoutEndpoint;
use App\Livewire\Shell\StopImpersonationEndpoint;
use App\Livewire\Superadmin\ExportRecentOrganizationsCsvEndpoint;
use App\Livewire\Tenant\DownloadInvoiceEndpoint;
use App\Livewire\Tenant\InvoiceHistory;
use App\Livewire\Tenant\SubmitMeterReading;
use App\Livewire\Tenant\TenantDashboard;
use App\Livewire\Tenant\TenantPortalRouteEndpoint;
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
    'csp report' => ['security.csp.report', CspViolationReportEndpoint::class.'@store'],
    'dashboard redirect' => ['dashboard', DashboardRedirectEndpoint::class.'@redirect'],
    'impersonation stop' => ['impersonation.stop', StopImpersonationEndpoint::class.'@stop'],
    'platform dashboard export' => [
        'filament.admin.pages.platform-dashboard.recent-organizations-export',
        ExportRecentOrganizationsCsvEndpoint::class.'@download',
    ],
    'guest locale switch' => ['language.switch', SwitchGuestLocaleEndpoint::class.'@change'],
    'tenant invoice download' => ['tenant.invoices.download', DownloadInvoiceEndpoint::class.'@download'],
    'tenant home alias' => ['tenant.home', TenantPortalRouteEndpoint::class.'@show'],
    'tenant reading alias' => ['tenant.readings.create', TenantPortalRouteEndpoint::class.'@show'],
    'tenant invoices alias' => ['tenant.invoices.index', TenantPortalRouteEndpoint::class.'@show'],
    'tenant property alias' => ['tenant.property.show', TenantPortalRouteEndpoint::class.'@show'],
    'tenant profile alias' => ['tenant.profile.edit', TenantPortalRouteEndpoint::class.'@show'],
    'logout' => ['logout', LogoutEndpoint::class.'@logout'],
]);

it('keeps routes/web.php free of inline route action callbacks', function (): void {
    $contents = File::get(base_path('routes/web.php'));

    expect($contents)->not->toMatch(
        '/Route::(?:get|post|put|patch|delete|options|match|any)\\([^;]*(?:fn \\(|function \\()/s',
    );
});

it('keeps app http controllers reserved for the base controller only', function (): void {
    $controllerFiles = collect(File::allFiles(app_path('Http/Controllers')))
        ->map(fn ($file) => $file->getRelativePathname())
        ->sort()
        ->values()
        ->all();

    expect($controllerFiles)->toBe([
        'Controller.php',
    ]);
});
