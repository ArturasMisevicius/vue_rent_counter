<?php

use App\Services\TimeRangeValidator;

test('TimeRangeValidator accepts valid non-overlapping zones', function () {
    $validator = new TimeRangeValidator();
    
    $zones = [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
    ];
    
    $errors = $validator->validate($zones);
    
    expect($errors)->toBeEmpty();
});

test('TimeRangeValidator detects overlapping zones', function () {
    $validator = new TimeRangeValidator();
    
    $zones = [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
        ['id' => 'overlap', 'start' => '22:00', 'end' => '08:00', 'rate' => 0.15],
    ];
    
    $errors = $validator->validate($zones);
    
    expect($errors)->toContain('Time zones cannot overlap.');
});

test('TimeRangeValidator detects gaps in coverage', function () {
    $validator = new TimeRangeValidator();
    
    $zones = [
        ['id' => 'day', 'start' => '07:00', 'end' => '22:00', 'rate' => 0.18],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
    ];
    
    $errors = $validator->validate($zones);
    
    expect($errors)->toHaveCount(1);
    expect($errors[0])->toContain('Gap detected starting at 22:00');
});

test('TimeRangeValidator handles midnight crossing zones', function () {
    $validator = new TimeRangeValidator();
    
    $zones = [
        ['id' => 'day', 'start' => '06:00', 'end' => '22:00', 'rate' => 0.18],
        ['id' => 'night', 'start' => '22:00', 'end' => '06:00', 'rate' => 0.10],
    ];
    
    $errors = $validator->validate($zones);
    
    expect($errors)->toBeEmpty();
});

test('TimeRangeValidator rejects empty zones array', function () {
    $validator = new TimeRangeValidator();
    
    $errors = $validator->validate([]);
    
    expect($errors)->toContain('At least one zone is required');
});

test('TimeRangeValidator handles three-zone configuration', function () {
    $validator = new TimeRangeValidator();
    
    $zones = [
        ['id' => 'peak', 'start' => '08:00', 'end' => '20:00', 'rate' => 0.20],
        ['id' => 'off-peak', 'start' => '20:00', 'end' => '23:00', 'rate' => 0.15],
        ['id' => 'night', 'start' => '23:00', 'end' => '08:00', 'rate' => 0.10],
    ];
    
    $errors = $validator->validate($zones);
    
    expect($errors)->toBeEmpty();
});
