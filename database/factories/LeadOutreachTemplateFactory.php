<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadOutreachChannel;
use App\Models\LeadOutreachTemplate;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadOutreachTemplate>
 */
class LeadOutreachTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Aruodas introduction',
            'channel' => LeadOutreachChannel::EMAIL,
            'subject' => 'Property management cooperation',
            'body' => 'Hello, we are interested in your property listing. Best regards, {organization_name}',
            'locale' => 'en',
            'is_active' => true,
        ];
    }
}
