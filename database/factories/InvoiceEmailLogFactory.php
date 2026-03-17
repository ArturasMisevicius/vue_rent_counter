<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceEmailLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceEmailLog>
 */
class InvoiceEmailLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'organization_id' => Organization::factory(),
            'sent_by_user_id' => User::factory(),
            'recipient_email' => fake()->safeEmail(),
            'subject' => 'Invoice ready',
            'status' => 'sent',
            'sent_at' => now(),
        ];
    }
}
