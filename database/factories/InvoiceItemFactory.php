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
                'Gyvatukas (Circulation Fee)',
                'Fixed Meter Fee',
            ]),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm³', 'month']),
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
            $currentReading = $previousReading + $attributes['quantity'];
            
            return [
                'meter_reading_snapshot' => [
                    'previous_reading' => $previousReading,
                    'current_reading' => $currentReading,
                    'meter_id' => fake()->numberBetween(1, 100),
                    'reading_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
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
            'description' => fake()->randomElement(['Heating', 'Hot Water', 'Gyvatukas (Circulation Fee)']),
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
