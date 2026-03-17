<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $user = User::factory()->admin()->for($organization);

        return [
            'organization_id' => $organization,
            'commentable_type' => Project::class,
            'commentable_id' => Project::factory()->for($organization)->for($user, 'creator'),
            'user_id' => $user,
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'is_internal' => false,
            'is_pinned' => false,
            'edited_at' => null,
        ];
    }
}
