<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

function buildStoreInvoiceRequest(?User $user = null): StoreInvoiceRequest
{
    $request = new StoreInvoiceRequest;

    if ($user !== null) {
        $request->setUserResolver(static fn (): User => $user);
    }

    return $request;
}

test('store invoice request validates required fields', function (): void {
    $request = buildStoreInvoiceRequest();
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_renter_id'))->toBeTrue()
        ->and($validator->errors()->has('billing_period_start'))->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('store invoice request validates tenant exists', function (): void {
    $user = User::factory()->admin(1001)->create();
    $request = buildStoreInvoiceRequest($user);

    $validator = Validator::make([
        'tenant_renter_id' => 99999,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_renter_id'))->toBeTrue();
});

test('store invoice request validates end date is after start date', function (): void {
    $tenant = Tenant::factory()->forTenantId(1001)->create();
    $user = User::factory()->admin(1001)->create();
    $request = buildStoreInvoiceRequest($user);

    $validator = Validator::make([
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-31',
        'billing_period_end' => '2024-01-01',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('store invoice request passes with valid scoped data', function (): void {
    $tenant = Tenant::factory()->forTenantId(1001)->create();
    $user = User::factory()->admin(1001)->create();
    $request = buildStoreInvoiceRequest($user);

    $validator = Validator::make([
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('store invoice request rejects tenant outside actor tenant scope', function (): void {
    Tenant::factory()->forTenantId(1001)->create();
    $outOfScopeTenant = Tenant::factory()->forTenantId(2002)->create();
    $user = User::factory()->admin(1001)->create();
    $request = buildStoreInvoiceRequest($user);

    $validator = Validator::make([
        'tenant_renter_id' => $outOfScopeTenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_renter_id'))->toBeTrue();
});

test('store invoice request allows superadmin to target any tenant', function (): void {
    $tenant = Tenant::factory()->forTenantId(2002)->create();
    $user = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    $request = buildStoreInvoiceRequest($user);

    $validator = Validator::make([
        'tenant_renter_id' => $tenant->id,
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('store invoice request has custom error messages', function (): void {
    $request = buildStoreInvoiceRequest();
    $messages = $request->messages();

    expect($messages)->toHaveKey('tenant_renter_id.required')
        ->and($messages)->toHaveKey('tenant_renter_id.exists')
        ->and($messages)->toHaveKey('billing_period_start.required')
        ->and($messages)->toHaveKey('billing_period_end.after');
});
