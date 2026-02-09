<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\EnhancedTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'commentable_type' => EnhancedTask::class,
            'commentable_id' => function (): int {
                return EnhancedTask::query()->value('id') ?? EnhancedTask::factory()->create()->id;
            },
            'user_id' => function (): int {
                return User::query()->where('tenant_id', 1)->value('id')
                    ?? User::factory()->manager(1)->create()->id;
            },
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'is_internal' => fake()->boolean(40),
            'is_pinned' => false,
            'edited_at' => null,
        ];
    }

    public function forCommentable(Model $model): static
    {
        return $this->state(fn () => [
            'commentable_type' => $model::class,
            'commentable_id' => $model->getKey(),
        ]);
    }
}
