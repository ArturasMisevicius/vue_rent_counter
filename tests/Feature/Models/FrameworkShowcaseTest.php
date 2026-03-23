<?php

declare(strict_types=1);

use App\Models\FrameworkShowcase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts tags and featured flags correctly', function () {
    $showcase = FrameworkShowcase::factory()->create([
        'tags' => ['livewire', 'filament'],
        'is_featured' => true,
    ]);

    expect($showcase->fresh()->tags)
        ->toBe(['livewire', 'filament'])
        ->and($showcase->fresh()->is_featured)
        ->toBeTrue();
});
