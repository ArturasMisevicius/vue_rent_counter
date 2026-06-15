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
            'project_reference_prefix' => 'PROJ-',
            'project_reference_sequence' => 0,
            'project_completion_mode' => 'manual',
            'project_budget_alert_threshold_percent' => 10,
            'project_schedule_alert_threshold_days' => 30,
            'notification_preferences' => [
                'new_invoice_generated' => true,
                'invoice_overdue' => true,
                'tenant_submits_reading' => true,
                'subscription_expiring' => true,
            ],
            'auto_generation_enabled' => false,
            'billing_frequency' => 'monthly',
            'invoice_generation_day' => 1,
            'reading_deadline_day' => 5,
            'payment_due_days' => 14,
            'send_created_notification' => true,
            'send_reminders' => true,
            'reminder_days_before_deadline' => [3, 1],
            'timezone' => 'UTC',
            'default_currency' => 'EUR',
            'kyc_required' => false,
            'required_document_types' => null,
            'require_expiry_date' => false,
            'block_portal_until_verified' => false,
            'block_invoice_download_until_verified' => false,
            'block_reading_submission_until_verified' => false,
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
            'project_reference_prefix' => 'PROJ-',
            'project_reference_sequence' => 0,
            'notification_preferences' => [
                'invoice_reminders' => true,
                'payment_receipts' => true,
                'reading_deadline_alerts' => true,
            ],
            'auto_generation_enabled' => true,
            'billing_frequency' => 'monthly',
            'invoice_generation_day' => 1,
            'reading_deadline_day' => 5,
            'payment_due_days' => 14,
            'send_created_notification' => true,
            'send_reminders' => true,
            'reminder_days_before_deadline' => [3, 1],
            'timezone' => 'UTC',
            'default_currency' => 'EUR',
        ]);
    }

    public function automaticProjectCompletion(): static
    {
        return $this->state([
            'project_completion_mode' => 'automatic_from_tasks',
        ]);
    }
}
