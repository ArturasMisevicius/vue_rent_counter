<?php

declare(strict_types=1);

use App\Models\FrameworkShowcase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows superadmins to access the framework studio and demo resource', function () {
    $superadmin = User::factory()->superadmin()->create();
    $showcase = FrameworkShowcase::factory()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.framework-studio'))
        ->assertSuccessful()
        ->assertSeeText('Framework Studio');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.framework-showcases.index'))
        ->assertSuccessful()
        ->assertSeeText($showcase->title)
        ->assertSeeText('Share preview');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.framework-showcases.view', ['record' => $showcase]))
        ->assertSuccessful()
        ->assertSeeText($showcase->title);
});

it('forbids tenants from the framework studio surface', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.framework-studio'))
        ->assertForbidden();
});
