<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\GyvatukasCalculationAudit;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use App\Services\GyvatukasCalculatorSecure;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = app(GyvatukasCalculatorSecure::class);
});

describe('Authorization', function () {
    test('superadmin can calculate for any building', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $building = Building::factory()->create(['tenant_id' => 999]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($superadmin);

        expect(fn() => $this->calculator->calculate($building, now()))->not->toThrow(AuthorizationException::class);
    });

    test('admin can calculate for buildings in their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        expect(fn() => $this->calculator->calculate($building, now()))->not->toThrow(AuthorizationException::class);
    });

    test('admin cannot calculate for buildings in other tenants', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 2]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(AuthorizationException::class);
    });

    test('manager can calculate for buildings in their tenant', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($manager);

        expect(fn() => $this->calculator->calculate($building, now()))->not->toThrow(AuthorizationException::class);
    });

    test('manager cannot calculate for buildings in other tenants', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 2]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($manager);

        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(AuthorizationException::class);
    });

    test('tenant cannot calculate', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($tenant);

        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(AuthorizationException::class);
    });

    test('unauthenticated user cannot calculate', function () {
        $building = Building::factory()->create();
        Property::factory()->create(['building_id' => $building->id]);

        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(AuthorizationException::class);
    });
});

describe('Rate Limiting', function () {
    test('enforces per-user rate limit', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        // Clear rate limiter
        RateLimiter::clear('gyvatukas:user:' . $admin->id);

        // Make 10 calculations (should succeed)
        for ($i = 0; $i < 10; $i++) {
            $this->calculator->calculate($building, now());
        }

        // 11th calculation should fail
        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(ThrottleRequestsException::class);
    });

    test('enforces per-tenant rate limit', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        // Clear rate limiters
        RateLimiter::clear('gyvatukas:user:' . $admin->id);
        RateLimiter::clear('gyvatukas:tenant:1');

        // Simulate 100 calculations by hitting tenant limit directly
        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit('gyvatukas:tenant:1', 60);
        }

        // Next calculation should fail
        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(ThrottleRequestsException::class);
    });
});

describe('Input Validation', function () {
    test('rejects building without properties', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        // No properties created

        $this->actingAs($admin);

        expect(fn() => $this->calculator->calculate($building, now()))->toThrow(\InvalidArgumentException::class);
    });

    test('rejects future billing month', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        $futureMonth = Carbon::now()->addMonths(2);

        expect(fn() => $this->calculator->calculate($building, $futureMonth))->toThrow(\InvalidArgumentException::class);
    });

    test('rejects billing month too far in past', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        $oldMonth = Carbon::parse('2019-01-01');

        expect(fn() => $this->calculator->calculate($building, $oldMonth))->toThrow(\InvalidArgumentException::class);
    });

    test('rejects invalid distribution method', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        expect(fn() => $this->calculator->distributeCirculationCost($building, 100.0, 'invalid', $admin))
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('Audit Trail', function () {
    test('creates audit record for each calculation', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['building_id' => $building->id]);

        // Create meters and readings
        $heatingMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'heating']);
        $waterMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'water_hot']);

        $month = Carbon::create(2024, 6, 1);
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodStart, 'value' => 1000]);
        MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodEnd, 'value' => 2000]);
        MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodStart, 'value' => 10]);
        MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodEnd, 'value' => 20]);

        $this->actingAs($admin);

        $this->calculator->calculate($building, $month);

        $audit = GyvatukasCalculationAudit::latest()->first();

        expect($audit)->not->toBeNull();
        expect($audit->building_id)->toBe($building->id);
        expect($audit->tenant_id)->toBe($building->tenant_id);
        expect($audit->calculated_by_user_id)->toBe($admin->id);
        expect($audit->billing_month)->toBe($month->format('Y-m-d'));
        expect($audit->season)->toBe('summer');
        expect($audit->circulation_energy)->toBeGreaterThan(0);
        expect($audit->calculation_metadata)->toHaveKey('duration_ms');
        expect($audit->calculation_metadata)->toHaveKey('query_count');
    });

    test('audit record includes performance metrics', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        $this->calculator->calculate($building, now());

        $audit = GyvatukasCalculationAudit::latest()->first();

        expect($audit->calculation_metadata['duration_ms'])->toBeGreaterThan(0);
        expect($audit->calculation_metadata['query_count'])->toBeGreaterThan(0);
        expect($audit->calculation_metadata)->toHaveKey('php_version');
        expect($audit->calculation_metadata)->toHaveKey('laravel_version');
    });
});

describe('Logging Security', function () {
    test('logs do not contain raw building IDs', function () {
        Log::spy();

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        $this->calculator->calculate($building, now());

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Gyvatukas calculation completed', \Mockery::on(function ($context) use ($building) {
                // Should have building_hash, not building_id
                return isset($context['building_hash']) 
                    && !isset($context['building_id'])
                    && strlen($context['building_hash']) === 8;
            }));
    });

    test('warning logs use hashed building IDs', function () {
        Log::spy();

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1, 'gyvatukas_summer_average' => null]);
        Property::factory()->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        // Trigger winter calculation with missing summer average
        $this->calculator->calculate($building, Carbon::create(2024, 1, 1));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Missing or invalid summer average for building', \Mockery::on(function ($context) {
                return isset($context['building_hash']) && !isset($context['building_id']);
            }));
    });
});

describe('Financial Precision', function () {
    test('uses BCMath for calculations', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['building_id' => $building->id]);

        $heatingMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'heating']);
        $waterMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'water_hot']);

        $month = Carbon::create(2024, 6, 1);
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();

        // Use values that would cause float precision errors
        MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodStart, 'value' => 1000.1]);
        MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodEnd, 'value' => 2000.2]);
        MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodStart, 'value' => 10.1]);
        MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodEnd, 'value' => 20.2]);

        $this->actingAs($admin);

        $result = $this->calculator->calculate($building, $month);

        // Result should be precise to 2 decimal places
        expect($result)->toBe(round($result, 2));
    });

    test('distribution uses BCMath for precision', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        
        // Create 3 properties
        Property::factory()->count(3)->create(['building_id' => $building->id]);

        $this->actingAs($admin);

        // Distribute amount that would cause float precision errors
        $distribution = $this->calculator->distributeCirculationCost($building, 100.01, 'equal', $admin);

        // Sum should equal original amount (within precision)
        $sum = array_sum($distribution);
        expect(abs($sum - 100.01))->toBeLessThan(0.01);
    });
});

describe('Performance', function () {
    test('uses eager loading to prevent N+1 queries', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        
        // Create 10 properties with meters
        $properties = Property::factory()->count(10)->create(['building_id' => $building->id]);
        
        foreach ($properties as $property) {
            $heatingMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'heating']);
            $waterMeter = Meter::factory()->create(['property_id' => $property->id, 'type' => 'water_hot']);
            
            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            
            MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodStart, 'value' => 1000]);
            MeterReading::factory()->create(['meter_id' => $heatingMeter->id, 'reading_date' => $periodEnd, 'value' => 2000]);
            MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodStart, 'value' => 10]);
            MeterReading::factory()->create(['meter_id' => $waterMeter->id, 'reading_date' => $periodEnd, 'value' => 20]);
        }

        $this->actingAs($admin);

        $this->calculator->calculate($building, Carbon::create(2024, 6, 1));

        $audit = GyvatukasCalculationAudit::latest()->first();

        // Should use ~6 queries regardless of building size (eager loading)
        expect($audit->calculation_metadata['query_count'])->toBeLessThanOrEqual(10);
    });
});
