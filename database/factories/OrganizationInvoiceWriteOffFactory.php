<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationInvoiceWriteOff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationInvoiceWriteOff>
 */
class OrganizationInvoiceWriteOffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 20, 500),
            'reason' => fake()->sentence(),
            'written_off_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
