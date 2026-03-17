<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceReminderLog>
 */
class InvoiceReminderLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'organization_id' => Organization::factory(),
            'sent_by_user_id' => User::factory(),
            'recipient_email' => fake()->safeEmail(),
            'channel' => 'email',
            'sent_at' => now(),
            'notes' => null,
        ];
    }
}
