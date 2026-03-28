<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationSetting>
 */
class OrganizationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'billing_contact_name' => fake()->name(),
            'billing_contact_email' => fake()->safeEmail(),
            'billing_contact_phone' => fake()->phoneNumber(),
            'payment_instructions' => fake()->sentence(),
            'invoice_footer' => fake()->sentence(),
            'notification_preferences' => [
                'new_invoice_generated' => true,
                'invoice_overdue' => true,
                'tenant_submits_reading' => true,
                'subscription_expiring' => true,
            ],
        ];
    }

    public function demoBilling(string $shortName, string $email, string $phone): static
    {
        return $this->state([
            'billing_contact_name' => sprintf('%s Billing Team', $shortName),
            'billing_contact_email' => $email,
            'billing_contact_phone' => $phone,
            'payment_instructions' => 'Pay by bank transfer and include your invoice reference.',
            'invoice_footer' => 'Thank you for paying on time.',
            'notification_preferences' => [
                'invoice_reminders' => true,
                'payment_receipts' => true,
                'reading_deadline_alerts' => true,
            ],
        ]);
    }
}
