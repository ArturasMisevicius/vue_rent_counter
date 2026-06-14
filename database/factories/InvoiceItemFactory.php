<?php

namespace Database\Factories;

use App\Enums\InvoiceItemSourceType;
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
            'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
            'source_id' => null,
            'service_configuration_id' => null,
            'utility_service_id' => null,
            'tariff_id' => null,
            'provider_id' => null,
            'title' => 'Utility service charge',
            'description' => fake()->randomElement([
                'Electricity charge',
                'Water supply',
                'Heating charge',
                'Shared services fee',
            ]),
            'description_for_tenant' => fn (array $attributes): string => (string) $attributes['description'],
            'internal_note' => null,
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm3', 'month']),
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => round($quantity * $unitPrice, 2),
            'currency' => 'EUR',
            'formula_label' => 'quantity x unit price',
            'calculation_snapshot' => null,
            'tenant_visible' => true,
            'sort_order' => 0,
            'meter_reading_snapshot' => null,
            'service_snapshot' => null,
            'tariff_snapshot' => null,
            'provider_snapshot' => null,
        ];
    }
}
