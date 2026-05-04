<?php

use App\Filament\Pages\Profile;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('hides the admin-only settings navigation item for managers', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText($manager->name)
        ->assertDontSeeText($manager->email)
        ->assertDontSeeText('Profile')
        ->assertDontSeeText('Settings');
});

it('lets managers update their shared account details from the profile page', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    $component = Livewire::actingAs($manager)
        ->test(Profile::class)
        ->set('profileForm.name', 'Manager Updated')
        ->set('profileForm.email', 'manager.updated@example.com')
        ->set('profileForm.phone', '+37060000000')
        ->set('profileForm.locale', 'lt')
        ->call('saveChanges')
        ->assertHasNoErrors();

    $component
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'manager-new-password')
        ->set('passwordForm.password_confirmation', 'manager-new-password')
        ->call('saveChanges')
        ->assertHasNoErrors();

    expect($manager->fresh())
        ->name->toBe('Manager Updated')
        ->email->toBe('manager.updated@example.com')
        ->phone->toBe('+37060000000')
        ->locale->toBe('lt')
        ->and(Hash::check('manager-new-password', $manager->fresh()->password))->toBeTrue();
});
