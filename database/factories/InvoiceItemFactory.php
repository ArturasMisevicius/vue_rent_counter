<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 150);
        $unitPrice = fake()->randomFloat(4, 0.05, 2.50);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->randomElement([
                'Electricity charge',
                'Water supply',
                'Heating charge',
                'Shared services fee',
            ]),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm3', 'month']),
            'unit_price' => $unitPrice,
            'total' => round($quantity * $unitPrice, 2),
            'meter_reading_snapshot' => null,
        ];
    }
}
