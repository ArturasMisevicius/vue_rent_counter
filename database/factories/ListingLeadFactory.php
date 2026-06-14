<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ListingLeadStatus;
use App\Models\LeadContact;
use App\Models\LeadSource;
use App\Models\ListingLead;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingLead>
 */
class ListingLeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'lead_source_id' => LeadSource::factory(),
            'import_batch_id' => null,
            'lead_contact_id' => LeadContact::factory(),
            'external_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'source_url' => 'https://www.aruodas.lt/butai/vilniuje/'.fake()->unique()->numberBetween(100000, 999999),
            'listing_title' => fake()->randomElement(['2-room flat', 'House near park', 'Apartment in Vilnius']),
            'property_address' => fake()->streetAddress(),
            'city' => 'Vilnius',
            'district' => 'Naujamiestis',
            'property_type' => 'apartment',
            'area' => fake()->randomFloat(2, 35, 120),
            'rooms' => fake()->numberBetween(1, 5),
            'floor' => (string) fake()->numberBetween(1, 8),
            'price' => fake()->randomFloat(2, 80000, 250000),
            'currency' => 'EUR',
            'owner_name' => fake()->name(),
            'owner_phone' => '+370 600 '.fake()->numberBetween(10000, 99999),
            'owner_email' => fake()->safeEmail(),
            'contact_raw' => null,
            'description' => fake()->paragraph(),
            'status' => ListingLeadStatus::NEW,
            'duplicate_reasons' => [],
            'raw_payload' => [],
            'assigned_to_user_id' => null,
            'last_contacted_at' => null,
            'next_follow_up_at' => null,
            'converted_property_id' => null,
            'converted_at' => null,
            'archived_at' => null,
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state([
            'organization_id' => $organization->id,
            'lead_source_id' => LeadSource::factory()->state(['organization_id' => $organization->id]),
            'lead_contact_id' => LeadContact::factory()->state(['organization_id' => $organization->id]),
        ]);
    }
}
