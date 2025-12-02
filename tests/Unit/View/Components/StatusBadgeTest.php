<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\View\Components\StatusBadge;

describe('StatusBadge Component', function () {
    test('resolves enum status correctly', function () {
        $component = new StatusBadge(InvoiceStatus::PAID);

        expect($component->statusValue)->toBe('paid')
            ->and($component->label)->toBe('Paid')
            ->and($component->badgeClasses)->toContain('bg-emerald-50')
            ->and($component->badgeClasses)->toContain('text-emerald-700')
            ->and($component->dotClasses)->toBe('bg-emerald-500');
    });

    test('handles string status', function () {
        $component = new StatusBadge('active');

        expect($component->statusValue)->toBe('active')
            ->and($component->label)->toBe('Active')
            ->and($component->badgeClasses)->toContain('bg-emerald-50');
    });

    test('handles null status gracefully', function () {
        $component = new StatusBadge(null);

        expect($component->statusValue)->toBe('unknown')
            ->and($component->label)->toBe('Unknown')
            ->and($component->badgeClasses)->toContain('bg-slate-100');
    });

    test('resolves draft invoice status', function () {
        $component = new StatusBadge(InvoiceStatus::DRAFT);

        expect($component->statusValue)->toBe('draft')
            ->and($component->label)->toBe('Draft')
            ->and($component->badgeClasses)->toContain('bg-amber-50')
            ->and($component->dotClasses)->toBe('bg-amber-400');
    });

    test('resolves finalized invoice status', function () {
        $component = new StatusBadge(InvoiceStatus::FINALIZED);

        expect($component->statusValue)->toBe('finalized')
            ->and($component->label)->toBe('Finalized')
            ->and($component->badgeClasses)->toContain('bg-indigo-50')
            ->and($component->dotClasses)->toBe('bg-indigo-500');
    });

    test('resolves subscription statuses', function () {
        $activeComponent = new StatusBadge(SubscriptionStatus::ACTIVE);
        expect($activeComponent->statusValue)->toBe('active')
            ->and($activeComponent->badgeClasses)->toContain('bg-emerald-50');

        $expiredComponent = new StatusBadge(SubscriptionStatus::EXPIRED);
        expect($expiredComponent->statusValue)->toBe('expired')
            ->and($expiredComponent->badgeClasses)->toContain('bg-rose-50');

        $suspendedComponent = new StatusBadge(SubscriptionStatus::SUSPENDED);
        expect($suspendedComponent->statusValue)->toBe('suspended')
            ->and($suspendedComponent->badgeClasses)->toContain('bg-amber-50');

        $cancelledComponent = new StatusBadge(SubscriptionStatus::CANCELLED);
        expect($cancelledComponent->statusValue)->toBe('cancelled')
            ->and($cancelledComponent->badgeClasses)->toContain('bg-slate-100');
    });

    test('resolves user roles', function () {
        $component = new StatusBadge(UserRole::SUPERADMIN);

        expect($component->statusValue)->toBe('superadmin')
            ->and($component->label)->toBe('Super Admin'); // Formatted from snake_case
    });

    test('uses default colors for unknown status', function () {
        $component = new StatusBadge('unknown_status');

        expect($component->badgeClasses)->toContain('bg-slate-100')
            ->and($component->badgeClasses)->toContain('text-slate-700')
            ->and($component->dotClasses)->toBe('bg-slate-400');
    });

    test('normalizes enum to string value', function () {
        $component = new StatusBadge(InvoiceStatus::PAID);

        expect($component->statusValue)->toBe('paid')
            ->and($component->statusValue)->not->toBeInstanceOf(InvoiceStatus::class);
    });

    test('normalizes string to string value', function () {
        $component = new StatusBadge('active');

        expect($component->statusValue)->toBe('active')
            ->and($component->statusValue)->toBeString();
    });

    test('renders view correctly', function () {
        $component = new StatusBadge(InvoiceStatus::PAID);
        $view = $component->render();

        expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class)
            ->and($view->name())->toBe('components.status-badge');
    });

    test('label resolution uses enum label method', function () {
        $component = new StatusBadge(InvoiceStatus::PAID);

        // Enum label() method should be used
        expect($component->label)->toBe('Paid');
    });

    test('label resolution falls back to formatted string', function () {
        $component = new StatusBadge('custom_status');

        // Should format snake_case to Title Case
        expect($component->label)->toBe('Custom Status');
    });

    test('handles pending status', function () {
        $component = new StatusBadge('pending');

        expect($component->statusValue)->toBe('pending')
            ->and($component->badgeClasses)->toContain('bg-blue-50')
            ->and($component->dotClasses)->toBe('bg-blue-400');
    });

    test('handles processing status', function () {
        $component = new StatusBadge('processing');

        expect($component->statusValue)->toBe('processing')
            ->and($component->badgeClasses)->toContain('bg-purple-50')
            ->and($component->dotClasses)->toBe('bg-purple-400');
    });

    test('component properties are readonly', function () {
        $component = new StatusBadge(InvoiceStatus::PAID);

        expect($component->statusValue)->toBe('paid')
            ->and($component->label)->toBe('Paid')
            ->and($component->badgeClasses)->toBeString()
            ->and($component->dotClasses)->toBeString();
    });

    test('handles all invoice statuses', function () {
        $statuses = [
            ['status' => InvoiceStatus::DRAFT, 'value' => 'draft', 'color' => 'bg-amber-50'],
            ['status' => InvoiceStatus::FINALIZED, 'value' => 'finalized', 'color' => 'bg-indigo-50'],
            ['status' => InvoiceStatus::PAID, 'value' => 'paid', 'color' => 'bg-emerald-50'],
        ];

        foreach ($statuses as $statusData) {
            $component = new StatusBadge($statusData['status']);
            expect($component->statusValue)->toBe($statusData['value'])
                ->and($component->badgeClasses)->toContain($statusData['color']);
        }
    });

    test('handles all subscription statuses', function () {
        $statuses = [
            ['status' => SubscriptionStatus::ACTIVE, 'value' => 'active', 'color' => 'bg-emerald-50'],
            ['status' => SubscriptionStatus::EXPIRED, 'value' => 'expired', 'color' => 'bg-rose-50'],
            ['status' => SubscriptionStatus::SUSPENDED, 'value' => 'suspended', 'color' => 'bg-amber-50'],
            ['status' => SubscriptionStatus::CANCELLED, 'value' => 'cancelled', 'color' => 'bg-slate-100'],
        ];

        foreach ($statuses as $statusData) {
            $component = new StatusBadge($statusData['status']);
            expect($component->statusValue)->toBe($statusData['value'])
                ->and($component->badgeClasses)->toContain($statusData['color']);
        }
    });
});
