<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Blade;

it('renders badge with enum status', function (): void {
    $html = Blade::render('<x-status-badge :status="$status" />', [
        'status' => InvoiceStatus::DRAFT,
    ]);

    expect($html)
        ->toContain('inline-flex')
        ->toContain('items-center')
        ->toContain('bg-amber-50')
        ->toContain('text-amber-700')
        ->toContain('bg-amber-400');
});

it('renders badge with string status', function (): void {
    $html = Blade::render('<x-status-badge :status="\'paid\'" />');

    expect($html)
        ->toContain('inline-flex')
        ->toContain('bg-emerald-50')
        ->toContain('text-emerald-700')
        ->toContain('bg-emerald-500');
});

it('renders label text', function (): void {
    $html = Blade::render('<x-status-badge :status="$status" />', [
        'status' => InvoiceStatus::FINALIZED,
    ]);

    expect($html)->toContain('<span>');
});

it('renders status dot', function (): void {
    $html = Blade::render('<x-status-badge :status="\'active\'" />');

    expect($html)
        ->toContain('h-2.5')
        ->toContain('w-2.5')
        ->toContain('rounded-full');
});

it('merges additional classes', function (): void {
    $html = Blade::render('<x-status-badge :status="\'draft\'" class="ml-4" />');

    expect($html)
        ->toContain('ml-4')
        ->toContain('inline-flex');
});

it('renders all invoice statuses correctly', function (): void {
    foreach (InvoiceStatus::cases() as $status) {
        $html = Blade::render('<x-status-badge :status="$status" />', [
            'status' => $status,
        ]);

        expect($html)
            ->toContain('inline-flex')
            ->toContain('rounded-full')
            ->not->toBeEmpty();
    }
});

it('renders all subscription statuses correctly', function (): void {
    foreach (SubscriptionStatus::cases() as $status) {
        $html = Blade::render('<x-status-badge :status="$status" />', [
            'status' => $status,
        ]);

        expect($html)
            ->toContain('inline-flex')
            ->toContain('rounded-full')
            ->not->toBeEmpty();
    }
});

it('renders without php blocks', function (): void {
    $viewContent = file_get_contents(resource_path('views/components/status-badge.blade.php'));

    expect($viewContent)
        ->not->toContain('@php')
        ->not->toContain('<?php');
});

it('has proper accessibility structure', function (): void {
    $html = Blade::render('<x-status-badge :status="\'active\'" />');

    expect($html)->toContain('<span');
    expect($html)->toMatch('/<span[^>]*class="[^"]*inline-flex[^"]*"/');
});
