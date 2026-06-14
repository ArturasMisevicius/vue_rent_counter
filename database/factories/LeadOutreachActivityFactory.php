<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\LeadOutreachStatus;
use App\Models\LeadOutreachActivity;
use App\Models\ListingLead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadOutreachActivity>
 */
class LeadOutreachActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'listing_lead_id' => ListingLead::factory(),
            'lead_contact_id' => null,
            'user_id' => User::factory()->admin(),
            'channel' => LeadOutreachChannel::MANUAL,
            'direction' => LeadOutreachDirection::INTERNAL_NOTE,
            'subject' => null,
            'message_summary' => fake()->sentence(),
            'status' => LeadOutreachStatus::COMPLETED,
            'sent_at' => null,
            'received_at' => null,
            'next_follow_up_at' => null,
            'completed_at' => now(),
            'internal_correction_reason' => null,
        ];
    }
}
