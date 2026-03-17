<?php

use App\Livewire\Auth\AcceptInvitationPage;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Onboarding\WelcomePage;
use App\Livewire\PublicSite\HomepagePage;
use App\Livewire\Tenant\HomePage;
use App\Livewire\Tenant\InvoiceHistoryPage;
use App\Livewire\Tenant\ProfilePage;
use App\Livewire\Tenant\PropertyPage;
use App\Livewire\Tenant\ReadingCreatePage;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ->assertRedirect(route('tenant.profile.edit'));
});

it('maps controller-free web routes to closures', function (string $routeName): void {
    $route = app('router')->getRoutes()->getByName($routeName);

    expect($route)->not->toBeNull()
        ->and($route->getActionName())->toBe('Closure');
})->with([
    'favicon' => ['favicon'],
    'guest locale update' => ['locale.update'],
    'login store' => ['login.store'],
    'register store' => ['register.store'],
    'password email' => ['password.email'],
    'password update' => ['password.update'],
    'invitation store' => ['invitation.store'],
    'welcome store' => ['welcome.store'],
    'impersonation stop' => ['impersonation.stop'],
    'tenant invoice download' => ['tenant.invoices.download'],
    'tenant profile update' => ['tenant.profile.update'],
    'tenant profile password update' => ['tenant.profile.password.update'],
    'logout' => ['logout'],
]);
