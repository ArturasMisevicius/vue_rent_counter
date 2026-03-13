<?php

use App\Models\Tenant;
use Carbon\Carbon;
use Database\Seeders\TenantHistorySeeder;
use Database\Seeders\TestBuildingsSeeder;
use Database\Seeders\TestPropertiesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TestBuildingsSeeder::class);
    $this->seed(TestPropertiesSeeder::class);
});

test('tenant history seeder creates historical records per tenant', function () {
    $this->seed(TenantHistorySeeder::class);

    $historicalByTenant = Tenant::whereNotNull('lease_end')
        ->where('lease_end', '<', now())
        ->get()
        ->groupBy('tenant_id');

    expect($historicalByTenant[1]->count() ?? 0)->toBeGreaterThanOrEqual(100)
        ->and($historicalByTenant[2]->count() ?? 0)->toBeGreaterThanOrEqual(100);

    // property_tenant pivot should reflect the history
    expect(DB::table('property_tenant')->count())->toBeGreaterThanOrEqual(200);
});

test('tenant history seeder creates chronological assignments per property', function () {
    $this->seed(TenantHistorySeeder::class);

    $assignmentsByProperty = DB::table('property_tenant')
        ->orderBy('property_id')
        ->orderBy('assigned_at')
        ->get()
        ->groupBy('property_id');

    foreach ($assignmentsByProperty as $propertyId => $rows) {
        $previousVacatedAt = null;

        foreach ($rows as $row) {
            $assignedAt = Carbon::parse($row->assigned_at);
            $vacatedAt = $row->vacated_at
                ? Carbon::parse($row->vacated_at)
                : null;

            if ($previousVacatedAt) {
                expect($assignedAt->greaterThanOrEqualTo($previousVacatedAt))->toBeTrue();
            }

            if ($vacatedAt) {
                expect($vacatedAt->greaterThan($assignedAt))->toBeTrue();
                $previousVacatedAt = $vacatedAt;
            } else {
                $previousVacatedAt = $assignedAt;
            }
        }
    }
});
