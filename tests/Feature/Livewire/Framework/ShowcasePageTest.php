<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the livewire showcase route for superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('framework.livewire.showcase'))
        ->assertSuccessful()
        ->assertSeeText('Livewire 4 + Tailwind 4 Showcase')
        ->assertSeeText('High-voltage Livewire with CSS-first Tailwind tokens')
        ->assertSeeText('Open command palette')
        ->assertSeeText('Open preview modal')
        ->assertSeeText('Loading isolated metrics...')
        ->assertSeeHtml('wire:navigate')
        ->assertSeeHtml('wire:current=');
});

it('forbids non-superadmins from viewing the livewire showcase route', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('framework.livewire.showcase'))
        ->assertForbidden();
});

it('opens the command palette from the showcase component', function () {
    $superadmin = User::factory()->superadmin()->create();

    $component = Livewire::actingAs($superadmin)
        ->test('pages::framework.showcase')
        ->call('openPalette')
        ->assertDispatched('open-palette');

    expect($component->effects['dispatches'][0]['ref'])->toBe('palette');
});

it('opens the preview modal from the showcase component', function () {
    $superadmin = User::factory()->superadmin()->create();

    $component = Livewire::actingAs($superadmin)
        ->test('pages::framework.showcase')
        ->call('openPreviewModal')
        ->assertDispatched('open-preview-modal');

    expect($component->effects['dispatches'][0]['ref'])->toBe('previewModal');
});

it('validates and saves showcase state', function () {
    $superadmin = User::factory()->superadmin()->create();

    Livewire::actingAs($superadmin)
        ->test('pages::framework.showcase')
        ->set('headline', 'Updated showcase headline')
        ->set('notes', 'Validated notes for the framework lab.')
        ->call('save')
        ->assertSet('saved', true)
        ->assertSet('saveCount', 1)
        ->assertSeeText('Changes synced with validated showcase state.');
});

it('renders forwarded slot content in the framework alert component', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('framework.livewire.showcase'))
        ->assertSeeText('Route + namespace demo')
        ->assertSeeHtml('data-framework-alert');
});
