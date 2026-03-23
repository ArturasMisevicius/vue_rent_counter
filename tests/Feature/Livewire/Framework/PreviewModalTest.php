<?php

declare(strict_types=1);

use App\Models\FrameworkShowcase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the directory multi-file preview modal component', function () {
    $superadmin = User::factory()->superadmin()->create();
    FrameworkShowcase::factory()->count(2)->create();

    Livewire::actingAs($superadmin)
        ->test('framework.preview-modal')
        ->assertSeeText('Framework preview modal')
        ->assertSeeText('2 framework showcases currently exist in the demo resource.')
        ->assertSeeHtml('wire:show="isOpen"');
});

it('opens and confirms the preview modal', function () {
    $superadmin = User::factory()->superadmin()->create();

    Livewire::actingAs($superadmin)
        ->test('framework.preview-modal')
        ->call('open')
        ->assertSet('isOpen', true)
        ->call('confirm')
        ->assertDispatched('framework-preview-confirmed')
        ->assertSet('isOpen', false);
});
