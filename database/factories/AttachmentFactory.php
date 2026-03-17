<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $uploader = User::factory()->admin()->for($organization);

        return [
            'organization_id' => $organization,
            'attachable_type' => Project::class,
            'attachable_id' => Project::factory()->for($organization)->for($uploader, 'creator'),
            'uploaded_by_user_id' => $uploader,
            'filename' => fake()->uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1000, 500000),
            'disk' => 'local',
            'path' => 'attachments/'.fake()->uuid().'.pdf',
            'description' => null,
            'metadata' => null,
        ];
    }
}
