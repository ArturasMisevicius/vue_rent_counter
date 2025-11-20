<?php

namespace Database\Seeders;

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestMeterReadingsSeeder extends Seeder
{
    /**
     * Seed test meter readings for all meters.
     * 
     * Creates historical readings for the last 3 months:
     * - For electricity meters: separate day and night zone readings
     * - For other meters: single reading per month
     * - Values increment realistically based on meter type
     * - All readings entered by a manager user
     * - Monotonically increasing values ensured
     */
    public function run(): void
    {
        // Get a manager user to be the one who entered all readings
        $manager = User::where('role', UserRole::MANAGER)->first();
        
        if (!$manager) {
            throw new \RuntimeException(
                'No manager user found. Run TestUsersSeeder first.'
            );
        }

        $meters = Meter::all();

        foreach ($meters as $meter) {
            $this->createReadingsForMeter($meter, $manager->id);
        }
    }

    /**
     * Create readings for a specific meter over the last 3 months.
     *
     * @param Meter $meter
     * @param int $managerId
     * @return void
     */
    private function createReadingsForMeter(Meter $meter, int $managerId): void
    {
        // Starting value for the meter
        $currentValue = $this->getInitialValue($meter->type);

        // Create readings for last 3 months (plus current month = 4 readings total)
        for ($month = 3; $month >= 0; $month--) {
            $readingDate = Carbon::now()->subMonths($month)->startOfMonth();

            if ($meter->supports_zones) {
                // Electricity meter with day/night zones
                $this->createZonedReading($meter, $readingDate, $currentValue, 'day', $managerId);
                $this->createZonedReading($meter, $readingDate, $currentValue * 0.6, 'night', $managerId);
                
                // Increment for next month
                $currentValue += $this->getIncrement($meter->type);
            } else {
                // Single reading for non-zoned meters
                $this->createSingleReading($meter, $readingDate, $currentValue, $managerId);
                
                // Increment for next month
                $currentValue += $this->getIncrement($meter->type);
            }
        }
    }

    /**
     * Create a single meter reading.
     *
     * @param Meter $meter
     * @param Carbon $readingDate
     * @param float $value
     * @param int $managerId
     * @return void
     */
    private function createSingleReading(Meter $meter, Carbon $readingDate, float $value, int $managerId): void
    {
        MeterReading::create([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate,
            'value' => $value,
            'zone' => null,
            'entered_by' => $managerId,
        ]);
    }

    /**
     * Create a zoned meter reading (for electricity meters).
     *
     * @param Meter $meter
     * @param Carbon $readingDate
     * @param float $value
     * @param string $zone
     * @param int $managerId
     * @return void
     */
    private function createZonedReading(Meter $meter, Carbon $readingDate, float $value, string $zone, int $managerId): void
    {
        MeterReading::create([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate,
            'value' => $value,
            'zone' => $zone,
            'entered_by' => $managerId,
        ]);
    }

    /**
     * Get the initial starting value for a meter based on its type.
     *
     * @param MeterType $type
     * @return float
     */
    private function getInitialValue(MeterType $type): float
    {
        return match($type) {
            MeterType::ELECTRICITY => 1000.0,
            MeterType::WATER_COLD => 500.0,
            MeterType::WATER_HOT => 300.0,
            MeterType::HEATING => 2000.0,
        };
    }

    /**
     * Get realistic monthly increment for a meter based on its type.
     *
     * @param MeterType $type
     * @return float
     */
    private function getIncrement(MeterType $type): float
    {
        return match($type) {
            MeterType::ELECTRICITY => fake()->numberBetween(150, 250),
            MeterType::WATER_COLD => fake()->numberBetween(8, 15),
            MeterType::WATER_HOT => fake()->numberBetween(5, 12),
            MeterType::HEATING => fake()->numberBetween(80, 180),
        };
    }
}
