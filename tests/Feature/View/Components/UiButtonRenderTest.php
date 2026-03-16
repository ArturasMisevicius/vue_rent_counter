<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders the canonical ui button with default classes and slot content', function (): void {
    $html = Blade::render('<x-ui.button>Save changes</x-ui.button>');

    expect($html)
        ->toContain('<button')
        ->toContain('type="button"')
        ->toContain('inline-flex')
        ->toContain('rounded-xl')
        ->toContain('bg-indigo-600')
        ->toContain('Save changes');
});

it('renders loading and disabled states for the canonical ui button', function (): void {
    $html = Blade::render('<x-ui.button :loading="true" :disabled="true">Saving</x-ui.button>');

    expect($html)
        ->toContain('animate-spin')
        ->toContain('cursor-not-allowed opacity-60')
        ->toContain('disabled');
});

it('supports variant, size, and merged custom classes for the canonical ui button', function (): void {
    $html = Blade::render(
        '<x-ui.button variant="secondary" size="lg" class="w-full">Open</x-ui.button>'
    );

    expect($html)
        ->toContain('bg-white text-slate-700')
        ->toContain('px-5 py-3 text-base')
        ->toContain('w-full');
});

it('keeps the canonical ui button template free of inline php blocks', function (): void {
    $viewContent = file_get_contents(resource_path('views/components/ui/button.blade.php'));

    expect($viewContent)
        ->not->toContain('@php')
        ->not->toContain('<?php');
});
