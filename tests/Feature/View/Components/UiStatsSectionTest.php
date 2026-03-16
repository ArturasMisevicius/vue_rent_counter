<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders default grid classes for four-column stats sections', function (): void {
    $html = Blade::render(
        '<x-ui.stats-section class="mt-8"><div>Widget</div></x-ui.stats-section>'
    );

    expect($html)
        ->toContain('mt-8')
        ->toContain('grid grid-cols-1 gap-5')
        ->toContain('sm:grid-cols-2 lg:grid-cols-4');
});

it('renders heading and three-column layout when configured', function (): void {
    $html = Blade::render(
        '<x-ui.stats-section :title="$title" :columns="3"><div>Widget</div></x-ui.stats-section>',
        ['title' => 'Pending tasks']
    );

    expect($html)
        ->toContain('Pending tasks')
        ->toContain('text-lg font-medium text-slate-900')
        ->toContain('sm:grid-cols-3');
});

it('renders description and two-column layout when configured', function (): void {
    $html = Blade::render(
        '<x-ui.stats-section :description="$description" :columns="2"><div>Widget</div></x-ui.stats-section>',
        ['description' => 'Shared stat cards']
    );

    expect($html)
        ->toContain('Shared stat cards')
        ->toContain('text-sm text-slate-500')
        ->toContain('sm:grid-cols-2');
});

it('renders single-column layout when configured', function (): void {
    $html = Blade::render(
        '<x-ui.stats-section :columns="1"><div>Widget</div></x-ui.stats-section>'
    );

    expect($html)
        ->toContain('grid grid-cols-1 gap-5')
        ->toContain('sm:grid-cols-1')
        ->not->toContain('sm:grid-cols-2 lg:grid-cols-4');
});
