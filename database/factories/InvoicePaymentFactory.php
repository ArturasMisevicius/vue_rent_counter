<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoicePayment>
 */
class InvoicePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'organization_id' => Organization::factory(),
            'recorded_by_user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 5, 100),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'reference' => 'PAY-'.fake()->unique()->numerify('######'),
            'paid_at' => now(),
            'notes' => null,
        ];
    }
}
