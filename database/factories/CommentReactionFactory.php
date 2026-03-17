<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentReaction>
 */
class CommentReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['like', 'heart', 'laugh', 'wow']),
        ];
    }
}
