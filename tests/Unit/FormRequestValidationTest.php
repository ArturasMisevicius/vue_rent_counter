<?php

use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\StoreTariffRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\Meter;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('store meter reading request validates basic rules', function () {
    $user = User::factory()->create();
    $meter = Meter::factory()->create();

    $request = new StoreMeterReadingRequest();
    $validator = Validator::make([
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 100,
        'entered_by' => $user->id,
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('store meter reading request rejects future dates', function () {
    $user = User::factory()->create();
    $meter = Meter::factory()->create();

    $request = new StoreMeterReadingRequest();
    $validator = Validator::make([
        'meter_id' => $meter->id,
        'reading_date' => now()->addDay()->format('Y-m-d'),
        'value' => 100,
        'entered_by' => $user->id,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('reading_date'))->toBeTrue();
});

test('store tariff request validates flat tariff', function () {
    $provider = Provider::factory()->create();

    $request = new StoreTariffRequest();
    $validator = Validator::make([
        'provider_id' => $provider->id,
        'name' => 'Test Flat Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('store tariff request validates time of use tariff', function () {
    $provider = Provider::factory()->create();

    $request = new StoreTariffRequest();
    $validator = Validator::make([
        'provider_id' => $provider->id,
        'name' => 'Test Time of Use Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ], $request->rules());

    expect($validator->fails())->toBeFalse();
});

test('update meter reading request requires change reason', function () {
    $request = new UpdateMeterReadingRequest();
    $validator = Validator::make([
        'value' => 150,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('change_reason'))->toBeTrue();
});

test('update meter reading request validates minimum reason length', function () {
    $request = new UpdateMeterReadingRequest();
    $validator = Validator::make([
        'value' => 150,
        'change_reason' => 'short',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('change_reason'))->toBeTrue();
});
