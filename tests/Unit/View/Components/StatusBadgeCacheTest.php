<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\View\Components\StatusBadge;
use Illuminate\Support\Facades\Cache;

describe('StatusBadge Cache Management', function () {
    beforeEach(function () {
        Cache::flush();
    });

    test('cache is populated on first access', function () {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'paid' => 'Paid',
                'draft' => 'Draft',
            ]);

        $component = new StatusBadge(InvoiceStatus::PAID);
        
        expect($component->label)->toBe('Paid');
    });

    test('invalidateCache clears translation cache', function () {
        // Populate cache first
        $component = new StatusBadge(InvoiceStatus::PAID);
        
        // Verify cache exists
        expect(Cache::has('status-badge.translations'))
            ->toBeTrue();
        
        // Invalidate cache
        StatusBadge::invalidateCache();
        
        // Verify cache is cleared
        expect(Cache::has('status-badge.translations'))
            ->toBeFalse();
    });

    test('unknown status logging works in development', function () {
        app()->detectEnvironment(fn () => 'local');
        
        Log::shouldReceive('warning')
            ->once()
            ->with('StatusBadge: Unknown status value', [
                'status_value' => 'unknown_status',
                'available_statuses' => Mockery::type('array'),
            ]);

        $component = new StatusBadge('unknown_status');
        
        expect($component->badgeClasses)->toContain('bg-slate-100');
    });
});