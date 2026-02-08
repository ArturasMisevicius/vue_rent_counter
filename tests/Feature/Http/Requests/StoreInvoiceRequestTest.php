<?php

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;

test('store invoice request validates required fields', function () {
    $request = new StoreInvoiceRequest();
    
    $validator = Validator::make([], $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_renter_id'))->toBeTrue()
        ->and($validator->errors()->has('billing_period_start'))->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('store invoice request validates tenant exists', function () {
    $request = new StoreInvoiceRequest();
    
    $validator = Validator::make([
        'tenant_renter_id' => 99999,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_renter_id'))->toBeTrue();
});

test('store invoice request validates end date is after start date', function () {
    $tenant = Tenant::factory()->create();
    $request = new StoreInvoiceRequest();
    
    $validator = Validator::make([
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-31',
        'billing_period_end' => '2024-01-01',
    ], $request->rules());
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('store invoice request passes with valid data', function () {
    $tenant = Tenant::factory()->create();
    $request = new StoreInvoiceRequest();
    
    $validator = Validator::make([
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('store invoice request has custom error messages', function () {
    $request = new StoreInvoiceRequest();
    $messages = $request->messages();
    
    expect($messages)->toHaveKey('tenant_renter_id.required')
        ->and($messages)->toHaveKey('billing_period_start.required')
        ->and($messages)->toHaveKey('billing_period_end.after');
});
