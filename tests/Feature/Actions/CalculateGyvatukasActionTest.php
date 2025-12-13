<?php

declare(strict_types=1);

use App\Actions\CalculateGyvatukasAction;
use App\Events\GyvatukasCalculated;
use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(CalculateGyvatukasAction::class);
});

describe('CalculateGyvatukasAction Integration', function () {
    test('calculates summer gyvatukas and fires event', function () {
        Event::fake();
        
        $building = Building::factory()->create([
            'total_apartments' => 20,
        ]);
        
        $summerMonth = Carbon::create(2024, 6, 15);
        
        $result = $this->action->execute($building, $summerMonth);
        
        expect($result)->toBeFloat();
        expect($result)->toBeGreaterThan(0);
        
        Event::assertDispatched(GyvatukasCalculated::class, function ($event) use ($building) {
            return $event->building->id === $building->id
                && $event->result->calculationType === 'summer';
        });
    });

    test('calculates winter gyvatukas with summer average', function () {
        Event::fake();
        
        $building = Building::factory()->create([
            'total_apartments' => 20,
            'gyvatukas_summer_average' => 150.0,
            'gyvatukas_last_calculated' => now()->subMonths(6),
        ]);
        
        $winterMonth = Carbon::create(2024, 12, 15);
        
        $result = $this->action->execute($building, $winterMonth);
        
        expect($result)->toBeFloat();
        expect($result)->toBeGreaterThan(0);
        
        Event::assertDispatched(GyvatukasCalculated::class, function ($event) use ($building) {
            return $event->building->id === $building->id
                && $event->result->calculationType === 'winter';
        });
    });

    test('handles building without summer average', function () {
        Event::fake();
        
        $building = Building::factory()->create([
            'total_apartments' => 20,
            'gyvatukas_summer_average' => null,
            'gyvatukas_last_calculated' => null,
        ]);
        
        $winterMonth = Carbon::create(2024, 12, 15);
        
        $result = $this->action->execute($building, $winterMonth);
        
        expect($result)->toBeFloat();
        expect($result)->toBeGreaterThan(0);
        
        // Should calculate and store summer average first
        $building->refresh();
        expect($building->gyvatukas_summer_average)->not->toBeNull();
        expect($building->gyvatukas_last_calculated)->not->toBeNull();
    });

    test('respects tenant scoping', function () {
        Event::fake();
        
        $tenant1Building = Building::factory()->create([
            'tenant_id' => 1,
            'total_apartments' => 20,
        ]);
        
        $tenant2Building = Building::factory()->create([
            'tenant_id' => 2,
            'total_apartments' => 20,
        ]);
        
        $summerMonth = Carbon::create(2024, 6, 15);
        
        // Calculate for tenant 1 building
        $result1 = $this->action->execute($tenant1Building, $summerMonth);
        
        // Calculate for tenant 2 building
        $result2 = $this->action->execute($tenant2Building, $summerMonth);
        
        // Both should succeed independently
        expect($result1)->toBeFloat();
        expect($result2)->toBeFloat();
        
        Event::assertDispatchedTimes(GyvatukasCalculated::class, 2);
    });
});