<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
            'tenant_id' => null,
            'property_id' => null,
            'recorded_by_user_id' => User::factory(),
            'submitted_by_user_id' => null,
            'confirmed_by_user_id' => null,
            'amount' => fake()->randomFloat(2, 5, 100),
            'currency' => 'EUR',
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PaymentStatus::CONFIRMED,
            'payment_date' => now()->toDateString(),
            'reference' => 'PAY-'.fake()->unique()->numerify('######'),
            'transaction_id' => null,
            'paid_at' => now(),
            'confirmed_at' => now(),
            'notes' => null,
            'internal_note' => null,
            'tenant_comment' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::PENDING,
            'confirmed_by_user_id' => null,
            'confirmed_at' => null,
            'paid_at' => now(),
        ]);
    }
}
