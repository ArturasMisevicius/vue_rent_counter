<?php

use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;

test('creates billing period with valid dates', function () {
    $start = Carbon::parse('2024-01-01');
    $end = Carbon::parse('2024-01-31');
    
    $period = new BillingPeriod($start, $end);
    
    expect($period->start)->toEqual($start)
        ->and($period->end)->toEqual($end);
});

test('throws exception when end date is before start date', function () {
    $start = Carbon::parse('2024-01-31');
    $end = Carbon::parse('2024-01-01');
    
    new BillingPeriod($start, $end);
})->throws(InvalidArgumentException::class);

test('creates billing period from strings', function () {
    $period = BillingPeriod::fromStrings('2024-01-01', '2024-01-31');
    
    expect($period->start->format('Y-m-d'))->toBe('2024-01-01')
        ->and($period->end->format('Y-m-d'))->toBe('2024-01-31');
});

test('creates billing period for specific month', function () {
    $period = BillingPeriod::forMonth(2024, 3);
    
    expect($period->start->format('Y-m-d'))->toBe('2024-03-01')
        ->and($period->end->format('Y-m-d'))->toBe('2024-03-31');
});

test('calculates correct number of days', function () {
    $period = BillingPeriod::forMonth(2024, 2); // February 2024 (leap year)
    
    expect($period->days())->toBe(29);
});

test('checks if date is contained in period', function () {
    $period = BillingPeriod::fromStrings('2024-01-01', '2024-01-31');
    
    expect($period->contains(Carbon::parse('2024-01-15')))->toBeTrue()
        ->and($period->contains(Carbon::parse('2024-02-01')))->toBeFalse();
});

test('generates human-readable string representation', function () {
    $period = BillingPeriod::fromStrings('2024-01-01', '2024-01-31');
    
    expect($period->toString())->toBe('2024-01-01 to 2024-01-31');
});
