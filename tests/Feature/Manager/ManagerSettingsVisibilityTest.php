<?php

use App\Filament\Pages\Settings;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows only profile-related settings sections to managers', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful()
        ->assertSeeText('Settings')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertDontSeeText('Organization Settings')
        ->assertDontSeeText('Notification Preferences')
        ->assertDontSeeText('Subscription');
});

it('lets managers update shared account settings sections from the settings page', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    Livewire::actingAs($manager)
        ->test(Settings::class)
        ->set('profileForm.name', 'Manager Updated')
        ->set('profileForm.email', 'manager.updated@example.com')
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'manager-new-password')
        ->set('passwordForm.password_confirmation', 'manager-new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect($manager->fresh())
        ->name->toBe('Manager Updated')
        ->email->toBe('manager.updated@example.com')
        ->locale->toBe('lt')
        ->and(Hash::check('manager-new-password', $manager->fresh()->password))->toBeTrue();
});
