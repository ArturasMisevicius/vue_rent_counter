<?php

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
use App\Livewire\Tenant\HomePage;
use App\Livewire\Tenant\InvoiceHistoryPage;
use App\Livewire\Tenant\ProfilePage;
use App\Livewire\Tenant\PropertyPage;
use App\Livewire\Tenant\ReadingCreatePage;
use App\Livewire\Tenant\UpdatePasswordEndpoint;
use App\Livewire\Tenant\UpdateProfileEndpoint;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Password;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders migrated public and tenant routes as livewire pages', function (Closure $requestFactory, string $component): void {
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

            return $test->actingAs($tenant->user)->get(route('tenant.home'));
        },
        HomePage::class,
    ],
    'tenant reading create' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()
                ->withAssignedProperty()
                ->withMeters(1)
                ->create();

            return $test->actingAs($tenant->user)->get(route('tenant.readings.create'));
        },
        ReadingCreatePage::class,
    ],
    'tenant invoices' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()
                ->withAssignedProperty()
                ->withUnpaidInvoices(1)
                ->create();

            return $test->actingAs($tenant->user)->get(route('tenant.invoices.index'));
        },
        InvoiceHistoryPage::class,
    ],
    'tenant property' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()
                ->withAssignedProperty()
                ->withMeters(1)
                ->create();

            return $test->actingAs($tenant->user)->get(route('tenant.property.show'));
        },
        PropertyPage::class,
    ],
    'tenant profile' => [
        function ($test) {
            $tenant = TenantPortalFactory::new()->create();

            return $test->actingAs($tenant->user)->get(route('tenant.profile.edit'));
        },
        ProfilePage::class,
    ],
]);

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
    'tenant invoice download' => ['tenant.invoices.download', InvoiceHistoryPage::class.'@download'],
    'tenant profile update' => ['tenant.profile.update', UpdateProfileEndpoint::class.'@update'],
    'tenant profile password update' => ['tenant.profile.password.update', UpdatePasswordEndpoint::class.'@update'],
    'logout' => ['logout', LogoutEndpoint::class.'@logout'],
]);

it('keeps routes/web.php free of inline callbacks', function (): void {
    $contents = File::get(base_path('routes/web.php'));

    expect($contents)
        ->not->toContain('fn (')
        ->not->toContain('function (');
});
