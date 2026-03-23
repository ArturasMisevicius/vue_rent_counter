<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('does not expose the removed framework studio route for superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();

    actingAs($superadmin)
        ->get('/app/framework-studio')
        ->assertNotFound();
});

it('does not expose the removed framework studio route for tenants', function () {
    $tenant = User::factory()->tenant()->create();

    actingAs($tenant)
        ->get('/app/framework-studio')
        ->assertNotFound();
});
