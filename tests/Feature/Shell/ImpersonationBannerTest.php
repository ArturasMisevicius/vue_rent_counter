<?php

use App\Livewire\Shell\ImpersonationBanner;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the impersonation banner on admin pages when impersonation metadata exists', function () {
    $organization = Organization::factory()->create();
    $impersonator = User::factory()->superadmin()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->withSession(impersonationSessionFor($impersonator))
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('You are impersonating this account')
        ->assertSeeText($impersonator->name)
        ->assertSeeText($impersonator->email);
});

it('clears impersonation state and returns to the impersonators dashboard', function () {
    $organization = Organization::factory()->create();
    $impersonator = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->withSession(impersonationSessionFor($impersonator))
        ->post(route('impersonation.stop'))
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $this->assertAuthenticatedAs($impersonator);

    expect(session()->missing('impersonator_id'))->toBeTrue()
        ->and(session()->missing('impersonator_name'))->toBeTrue()
        ->and(session()->missing('impersonator_email'))->toBeTrue();
});

it('does not render the banner on admin pages when no impersonation session exists', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertDontSeeText('You are impersonating this account');
});

it('refreshes translated impersonation banner copy when the shell locale changes', function () {
    $organization = Organization::factory()->create();
    $impersonator = User::factory()->superadmin()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    app()->instance(ImpersonationService::class, new class($impersonator)
    {
        public function __construct(
            private readonly User $impersonator,
        ) {}

        /**
         * @return array{id: int, name: string, email: string}
         */
        public function current($request): array
        {
            return [
                'id' => $this->impersonator->id,
                'name' => $this->impersonator->name,
                'email' => $this->impersonator->email,
            ];
        }
    });

    $component = Livewire::actingAs($manager)
        ->test(ImpersonationBanner::class)
        ->assertSeeText(__('shell.impersonation.heading', [], 'en'))
        ->assertSeeText(__('shell.impersonation.actions.stop', [], 'en'));

    $manager->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($manager->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('shell.impersonation.heading', [], 'lt'))
        ->assertSeeText(__('shell.impersonation.actions.stop', [], 'lt'));
});

function impersonationSessionFor(User $impersonator): array
{
    return [
        'impersonator_id' => $impersonator->id,
        'impersonator_name' => $impersonator->name,
        'impersonator_email' => $impersonator->email,
        'impersonation_session_id' => (string) Str::uuid(),
        'impersonation_started_at' => now()->subMinutes(10)->toIso8601String(),
        'impersonation_expires_at' => now()->addMinutes(50)->toIso8601String(),
    ];
}
