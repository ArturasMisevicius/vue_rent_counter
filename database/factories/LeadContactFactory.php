<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Filament\Support\Admin\Leads\LeadDataNormalizer;
use App\Models\LeadContact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadContact>
 */
class LeadContactFactory extends Factory
{
    public function definition(): array
    {
        $phone = '+370 600 '.fake()->numberBetween(10000, 99999);
        $email = fake()->unique()->safeEmail();
        $normalizer = app(LeadDataNormalizer::class);

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'phone' => $phone,
            'email' => $email,
            'normalized_phone' => $normalizer->phone($phone),
            'normalized_email' => $normalizer->email($email),
            'preferred_channel' => null,
            'do_not_contact' => false,
            'do_not_contact_reason' => null,
            'do_not_contact_at' => null,
            'last_contacted_at' => null,
            'marked_do_not_contact_by_user_id' => null,
        ];
    }

    public function doNotContact(string $reason = 'Owner opted out'): static
    {
        return $this->state([
            'do_not_contact' => true,
            'do_not_contact_reason' => $reason,
            'do_not_contact_at' => now(),
        ]);
    }
}
