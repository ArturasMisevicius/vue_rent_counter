<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 10, 500);
        $unitPrice = fake()->randomFloat(4, 0.05, 2.00);
        
        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->randomElement([
                'Electricity - Day Rate',
                'Electricity - Night Rate',
                'Water Supply',
                'Water Sewage',
                'Heating',
                'Hot Water',
                'Fixed Meter Fee',
                'Internet (Fixed)',
            ]),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm³', 'month', 'L']),
            'unit_price' => $unitPrice,
            'total' => null,
            'meter_reading_snapshot' => null,
        ];
    }

    /**
     * Indicate that the invoice item has meter reading snapshot data.
     */
    public function withSnapshot(): static
    {
        return $this->state(function (array $attributes) {
            $previousReading = fake()->randomFloat(2, 1000, 5000);
            $quantity = (float) ($attributes['quantity'] ?? 0);
            $unitPrice = (float) ($attributes['unit_price'] ?? 0);
            $total = (float) ($attributes['total'] ?? ($quantity * $unitPrice));

            $currentReading = $previousReading + $quantity;
            $endReadingDate = fake()->dateTimeBetween('-1 month', 'now');
            $startReadingDate = (clone $endReadingDate)->modify('-1 month');
            
            return [
                'meter_reading_snapshot' => [
                    'service_configuration' => [
                        'id' => fake()->numberBetween(1, 5000),
                        'pricing_model' => 'consumption_based',
                        'rate_schedule' => [
                            'unit_rate' => $unitPrice,
                        ],
                        'distribution_method' => 'equal',
                        'snapshot_date' => now()->toISOString(),
                    ],
                    'utility_service' => [
                        'id' => fake()->numberBetween(1, 5000),
                        'name' => (string) ($attributes['description'] ?? 'Utility Service'),
                        'unit_of_measurement' => (string) ($attributes['unit'] ?? ''),
                    ],
                    'consumption' => [
                        'total_consumption' => $quantity,
                        'zone_consumption' => [],
                        'metadata' => [],
                    ],
                    'meters' => [
                        [
                            'meter_id' => fake()->numberBetween(1, 100),
                            'meter_serial' => fake()->unique()->numerify('LT-####-####'),
                            'zone' => null,
                            'start_reading_id' => null,
                            'start_value' => number_format((float) $previousReading, 2, '.', ''),
                            'start_date' => $startReadingDate->format('Y-m-d'),
                            'end_reading_id' => null,
                            'end_value' => number_format((float) $currentReading, 2, '.', ''),
                            'end_date' => $endReadingDate->format('Y-m-d'),
                            'consumption' => round($quantity, 3),
                        ],
                    ],
                    'calculation' => [
                        'total_amount' => $total,
                        'base_amount' => $total,
                        'adjustments' => [],
                        'consumption_amount' => $total,
                        'fixed_amount' => 0.0,
                        'tariff_snapshot' => [],
                        'calculation_details' => [
                            'unit_rate' => $unitPrice,
                        ],
                    ],
                ],
            ];
        });
    }

    /**
     * Indicate that the invoice item is for electricity.
     */
    public function electricity(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement(['Electricity - Day Rate', 'Electricity - Night Rate']),
            'unit' => 'kWh',
            'unit_price' => fake()->randomFloat(4, 0.10, 0.20),
        ]);
    }

    /**
     * Indicate that the invoice item is for water.
     */
    public function water(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement(['Water Supply', 'Water Sewage']),
            'unit' => 'm³',
            'unit_price' => fake()->randomFloat(4, 0.85, 1.30),
        ]);
    }

    /**
     * Indicate that the invoice item is for heating.
     */
    public function heating(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement(['Heating', 'Hot Water']),
            'unit' => 'kWh',
            'unit_price' => fake()->randomFloat(4, 0.08, 0.15),
        ]);
    }

    /**
     * Attach the item to a specific invoice.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Keep the total in sync with quantity and unit_price.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (InvoiceItem $item) {
            if ($item->total === null) {
                $item->total = round((float) $item->quantity * (float) $item->unit_price, 2);
            }
        })->afterCreating(function (InvoiceItem $item) {
            if ($item->getRawOriginal('total') === null) {
                $item->update([
                    'total' => round((float) $item->quantity * (float) $item->unit_price, 2),
                ]);
            }
        });
    }
}
