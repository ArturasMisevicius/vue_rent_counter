<?php

use App\Filament\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('blocks deleting tariffs from another organization when an authenticated user is present', function () {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $foreignProvider = Provider::factory()->forOrganization($otherOrganization)->create();
    $foreignTariff = Tariff::factory()->for($foreignProvider)->create();

    actingAs($manager);

    expect(fn () => app(DeleteTariffAction::class)->handle($foreignTariff))
        ->toThrow(AuthorizationException::class);

    expect(Tariff::query()->whereKey($foreignTariff->id)->exists())->toBeTrue();
});
