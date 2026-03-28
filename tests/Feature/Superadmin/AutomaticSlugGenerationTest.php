<?php

use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('removes the slug field from tag forms and auto-generates it on create and edit', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    actingAs($superadmin);

    Livewire::test(CreateTag::class)
        ->assertFormFieldDoesNotExist('slug')
        ->fillForm([
            'organization_id' => $organization->id,
            'name' => 'Priority Ops',
            'is_system' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tag = Tag::query()->firstOrFail();

    expect($tag->slug)->toBe('priority-ops');

    Livewire::test(EditTag::class, ['record' => $tag->getRouteKey()])
        ->assertFormFieldDoesNotExist('slug')
        ->fillForm([
            'organization_id' => $organization->id,
            'name' => 'Priority Operations',
            'is_system' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->fresh()->slug)->toBe('priority-operations');
});

it('ignores manual slug edits on existing generated-slug models and keeps the slug derived from the source field', function () {
    $organization = Organization::factory()->create([
        'name' => 'North Hall',
        'slug' => 'legacy-slug',
    ]);

    $organization->update([
        'slug' => 'manually-edited-slug',
    ]);

    expect($organization->fresh()->slug)->toBe('north-hall');
});

it('uses the shared generated slug contract for utility services on create and rename', function () {
    $organization = Organization::factory()->create();

    UtilityService::factory()->for($organization)->create([
        'name' => 'Cold Water',
        'slug' => 'cold-water',
    ]);

    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Cold Water',
        'slug' => null,
    ]);

    expect($utilityService->slug)->toBe('cold-water-2');

    $utilityService->update([
        'name' => 'Cold Water Shared',
    ]);

    expect($utilityService->fresh()->slug)->toBe('cold-water-shared');
});

it('hides tag slugs from list and view pages while keeping automatic generation', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $tag = Tag::factory()->for($organization)->create([
        'name' => 'Priority Ops',
        'slug' => 'priority-ops',
    ]);

    actingAs($superadmin);

    $this->get(route('filament.admin.resources.tags.index'))
        ->assertSuccessful()
        ->assertSeeText($tag->name)
        ->assertDontSeeText($tag->slug);

    $this->get(route('filament.admin.resources.tags.view', ['record' => $tag]))
        ->assertSuccessful()
        ->assertSeeText($tag->name)
        ->assertDontSeeText($tag->slug);
});
