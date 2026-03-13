<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Requests\GenerateBulkInvoicesRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

function buildGenerateBulkInvoicesRequest(?User $user = null): GenerateBulkInvoicesRequest
{
    $request = new GenerateBulkInvoicesRequest;

    if ($user !== null) {
        $request->setUserResolver(static fn (): User => $user);
    }

    return $request;
}

test('generate bulk invoices request validates required fields', function (): void {
    $request = buildGenerateBulkInvoicesRequest();
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('billing_period_start'))->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('generate bulk invoices request validates period ordering', function (): void {
    $user = User::factory()->admin(1001)->create();
    $request = buildGenerateBulkInvoicesRequest($user);

    $validator = Validator::make([
        'billing_period_start' => '2024-01-31',
        'billing_period_end' => '2024-01-01',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('billing_period_end'))->toBeTrue();
});

test('generate bulk invoices request allows scoped tenant ids', function (): void {
    $tenantA = Tenant::factory()->forTenantId(1001)->create();
    $tenantB = Tenant::factory()->forTenantId(1001)->create();
    $user = User::factory()->admin(1001)->create();
    $request = buildGenerateBulkInvoicesRequest($user);

    $validator = Validator::make([
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
        'tenant_ids' => [$tenantA->id, $tenantB->id],
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('generate bulk invoices request rejects out-of-scope tenant ids', function (): void {
    $tenantInScope = Tenant::factory()->forTenantId(1001)->create();
    $tenantOutOfScope = Tenant::factory()->forTenantId(2002)->create();
    $user = User::factory()->admin(1001)->create();
    $request = buildGenerateBulkInvoicesRequest($user);

    $validator = Validator::make([
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
        'tenant_ids' => [$tenantInScope->id, $tenantOutOfScope->id],
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_ids.1'))->toBeTrue();
});

test('generate bulk invoices request allows superadmin across tenant scopes', function (): void {
    $tenantA = Tenant::factory()->forTenantId(1001)->create();
    $tenantB = Tenant::factory()->forTenantId(2002)->create();
    $user = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    $request = buildGenerateBulkInvoicesRequest($user);

    $validator = Validator::make([
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
        'tenant_ids' => [$tenantA->id, $tenantB->id],
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});
