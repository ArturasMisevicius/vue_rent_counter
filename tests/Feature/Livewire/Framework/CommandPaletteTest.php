<?php

declare(strict_types=1);

use App\Livewire\Framework\CommandPalette;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('opens and filters framework commands', function () {
    $superadmin = User::factory()->superadmin()->create();

    Livewire::actingAs($superadmin)
        ->test(CommandPalette::class)
        ->assertSeeHtml('wire:show="isOpen"')
        ->call('open')
        ->assertSet('isOpen', true)
        ->set('query', 'report')
        ->assertSeeText('Open reports')
        ->assertDontSeeText('Manage framework showcases');
});
