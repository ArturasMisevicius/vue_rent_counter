<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows language switched flash message only once on profile page', function (): void {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    $message = __('common.language_switched');

    $response = $this->actingAs($superadmin)
        ->withSession(['success' => $message])
        ->get(route('superadmin.profile.show'));

    $response->assertOk();

    expect(substr_count($response->getContent(), $message))->toBe(1);
});
