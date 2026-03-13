# Advanced Relationships - Testing Guide

This document provides comprehensive testing strategies for the advanced relationship patterns.

## Table of Contents
1. [Factory Definitions](#factory-definitions)
2. [Unit Tests](#unit-tests)
3. [Feature Tests](#feature-tests)
4. [Relationship Integrity Tests](#relationship-integrity-tests)
5. [Performance Tests](#performance-tests)

---

## Factory Definitions

### Comment Factory

```php
// database/factories/CommentFactory.php
<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => User::factory(),
            'commentable_id' => Invoice::factory(),
            'commentable_type' => Invoice::class,
            'user_id' => User::factory(),
            'body' => $this->faker->paragraph(),
            'is_internal' => $this->faker->boolean(30),
            'is_pinned' => false,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => false,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function reply(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'commentable_id' => $parent->commentable_id,
            'commentable_type' => $parent->commentable_type,
        ]);
    }

    public function for($commentable): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_id' => $commentable->id,
            'commentable_type' => get_class($commentable),
            'tenant_id' => $commentable->tenant_id ?? $attributes['tenant_id'],
        ]);
    }
}
```

### Attachment Factory

```php
// database/factories/AttachmentFactory.php
<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $filename = $this->faker->uuid() . '.pdf';
        
        return [
            'tenant_id' => User::factory(),
            'attachable_id' => Invoice::factory(),
            'attachable_type' => Invoice::class,
            'uploaded_by' => User::factory(),
            'filename' => $filename,
            'original_filename' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1024, 5242880), // 1KB to 5MB
            'disk' => 'local',
            'path' => 'attachments/test/' . $filename,
            'description' => $this->faker->sentence(),
            'metadata' => [
                'uploaded_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'filename' => $this->faker->uuid() . '.jpg',
            'original_filename' => $this->faker->word() . '.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'attachments/test/' . $this->faker->uuid() . '.jpg',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
        ]);
    }

    public function for($attachable): static
    {
        return $this->state(fn (array $attributes) => [
            'attachable_id' => $attachable->id,
            'attachable_type' => get_class($attachable),
            'tenant_id' => $attachable->tenant_id ?? $attributes['tenant_id'],
        ]);
    }
}
```

### Tag Factory

```php
// database/factories/TagFactory.php
<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();
        
        return [
            'tenant_id' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(),
            'usage_count' => 0,
        ];
    }
}
```

### Activity Factory

```php
// database/factories/ActivityFactory.php
<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'tenant_id' => User::factory(),
            'log_name' => 'default',
            'description' => $this->faker->sentence(),
            'subject_id' => Invoice::factory(),
            'subject_type' => Invoice::class,
            'causer_id' => User::factory(),
            'causer_type' => User::class,
            'properties' => [
                'attributes' => ['status' => 'updated'],
            ],
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted']),
        ];
    }

    public function for($subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_id' => $subject->id,
            'subject_type' => get_class($subject),
            'tenant_id' => $subject->tenant_id ?? $attributes['tenant_id'],
        ]);
    }

    public function causedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);
    }
}
```

---

## Unit Tests

### Comment Model Tests

```php
// tests/Unit/Models/CommentTest.php
<?php

namespace Tests\Unit\Models;

use App\Models\Comment;
use App\Models\Invoice;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_commentable_model()
    {
        $invoice = Invoice::factory()->create();
        $comment = Comment::factory()->for($invoice)->create();

        $this->assertInstanceOf(Invoice::class, $comment->commentable);
        $this->assertEquals($invoice->id, $comment->commentable->id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }

    /** @test */
    public function it_can_have_a_parent_comment()
    {
        $parent = Comment::factory()->create();
        $reply = Comment::factory()->reply($parent)->create();

        $this->assertInstanceOf(Comment::class, $reply->parent);
        $this->assertEquals($parent->id, $reply->parent->id);
    }

    /** @test */
    public function it_can_have_multiple_replies()
    {
        $parent = Comment::factory()->create();
        $reply1 = Comment::factory()->reply($parent)->create();
        $reply2 = Comment::factory()->reply($parent)->create();

        $this->assertCount(2, $parent->replies);
    }

    /** @test */
    public function it_can_get_all_descendants_recursively()
    {
        $parent = Comment::factory()->create();
        $reply1 = Comment::factory()->reply($parent)->create();
        $reply2 = Comment::factory()->reply($reply1)->create();
        $reply3 = Comment::factory()->reply($reply2)->create();

        $descendants = $parent->descendants;

        $this->assertCount(1, $descendants); // Direct replies
        $this->assertCount(1, $descendants->first()->descendants); // Nested replies
    }

    /** @test */
    public function it_can_scope_to_top_level_comments()
    {
        $parent = Comment::factory()->create();
        Comment::factory()->reply($parent)->create();
        Comment::factory()->reply($parent)->create();

        $topLevel = Comment::topLevel()->get();

        $this->assertCount(1, $topLevel);
    }

    /** @test */
    public function it_can_scope_to_internal_comments()
    {
        Comment::factory()->internal()->create();
        Comment::factory()->public()->create();

        $internal = Comment::internal()->get();

        $this->assertCount(1, $internal);
        $this->assertTrue($internal->first()->is_internal);
    }

    /** @test */
    public function it_can_check_if_edited()
    {
        $comment = Comment::factory()->create(['edited_at' => null]);
        $this->assertFalse($comment->isEdited());

        $comment->markAsEdited();
        $this->assertTrue($comment->isEdited());
    }

    /** @test */
    public function it_can_check_if_reply()
    {
        $parent = Comment::factory()->create();
        $reply = Comment::factory()->reply($parent)->create();

        $this->assertFalse($parent->isReply());
        $this->assertTrue($reply->isReply());
    }
}
```

### Tag Model Tests

```php
// tests/Unit/Models/TagTest.php
<?php

namespace Tests\Unit\Models;

use App\Models\Tag;
use App\Models\Invoice;
use App\Models\Property;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_automatically_generates_slug_from_name()
    {
        $tag = Tag::factory()->create(['name' => 'Urgent Priority', 'slug' => null]);

        $this->assertEquals('urgent-priority', $tag->slug);
    }

    /** @test */
    public function it_can_be_attached_to_invoices()
    {
        $tag = Tag::factory()->create();
        $invoice = Invoice::factory()->create();

        $invoice->tags()->attach($tag->id);

        $this->assertCount(1, $tag->invoices);
        $this->assertEquals($invoice->id, $tag->invoices->first()->id);
    }

    /** @test */
    public function it_can_be_attached_to_properties()
    {
        $tag = Tag::factory()->create();
        $property = Property::factory()->create();

        $property->tags()->attach($tag->id);

        $this->assertCount(1, $tag->properties);
    }

    /** @test */
    public function it_updates_usage_count()
    {
        $tag = Tag::factory()->create(['usage_count' => 0]);
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        $invoice1->tags()->attach($tag->id);
        $invoice2->tags()->attach($tag->id);

        $tag->updateUsageCount();

        $this->assertEquals(2, $tag->fresh()->usage_count);
    }

    /** @test */
    public function it_can_scope_to_popular_tags()
    {
        Tag::factory()->create(['usage_count' => 10]);
        Tag::factory()->create(['usage_count' => 5]);
        Tag::factory()->create(['usage_count' => 1]);

        $popular = Tag::popular(2)->get();

        $this->assertCount(2, $popular);
        $this->assertEquals(10, $popular->first()->usage_count);
    }

    /** @test */
    public function it_can_scope_to_unused_tags()
    {
        Tag::factory()->create(['usage_count' => 0]);
        Tag::factory()->create(['usage_count' => 5]);

        $unused = Tag::unused()->get();

        $this->assertCount(1, $unused);
    }
}
```

---

## Feature Tests

### Comments Feature Test

```php
// tests/Feature/CommentsTest.php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Invoice;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_add_comment_to_invoice()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['tenant_id' => $user->tenant_id]);

        $this->actingAs($user);

        $comment = $invoice->addComment(
            body: 'Test comment',
            userId: $user->id,
            isInternal: false
        );

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'commentable_id' => $invoice->id,
            'commentable_type' => Invoice::class,
            'user_id' => $user->id,
            'body' => 'Test comment',
        ]);
    }

    /** @test */
    public function user_can_reply_to_comment()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['tenant_id' => $user->tenant_id]);
        $parent = Comment::factory()->for($invoice)->create();

        $this->actingAs($user);

        $reply = Comment::create([
            'tenant_id' => $user->tenant_id,
            'commentable_id' => $invoice->id,
            'commentable_type' => Invoice::class,
            'parent_id' => $parent->id,
            'user_id' => $user->id,
            'body' => 'Reply to comment',
        ]);

        $this->assertEquals($parent->id, $reply->parent_id);
        $this->assertCount(1, $parent->replies);
    }

    /** @test */
    public function comments_are_scoped_by_tenant()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $invoice1 = Invoice::factory()->create(['tenant_id' => $user1->tenant_id]);
        $invoice2 = Invoice::factory()->create(['tenant_id' => $user2->tenant_id]);

        Comment::factory()->for($invoice1)->create();
        Comment::factory()->for($invoice2)->create();

        $this->actingAs($user1);
        
        $comments = Comment::where('tenant_id', $user1->tenant_id)->get();
        
        $this->assertCount(1, $comments);
    }
}
```

### Tagging Feature Test

```php
// tests/Feature/TaggingTest.php
<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Property;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function property_can_be_tagged()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $user->tenant_id]);
        $tag = Tag::factory()->create(['tenant_id' => $user->tenant_id]);

        $this->actingAs($user);

        $property->attachTags([$tag], taggedBy: $user->id);

        $this->assertTrue($property->hasTag($tag));
        $this->assertCount(1, $property->tags);
    }

    /** @test */
    public function property_can_be_filtered_by_tag()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['tenant_id' => $user->tenant_id, 'slug' => 'urgent']);
        
        $property1 = Property::factory()->create(['tenant_id' => $user->tenant_id]);
        $property2 = Property::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $property1->attachTags([$tag]);

        $this->actingAs($user);

        $urgentProperties = Property::withTag('urgent')->get();

        $this->assertCount(1, $urgentProperties);
        $this->assertEquals($property1->id, $urgentProperties->first()->id);
    }

    /** @test */
    public function tag_usage_count_updates_correctly()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['tenant_id' => $user->tenant_id, 'usage_count' => 0]);
        
        $property1 = Property::factory()->create(['tenant_id' => $user->tenant_id]);
        $property2 = Property::factory()->create(['tenant_id' => $user->tenant_id]);

        $property1->attachTags([$tag]);
        $property2->attachTags([$tag]);

        $tag->updateUsageCount();

        $this->assertEquals(2, $tag->fresh()->usage_count);
    }
}
```

---

## Relationship Integrity Tests

```php
// tests/Feature/RelationshipIntegrityTest.php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RelationshipIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function deleting_invoice_cascades_to_comments()
    {
        $invoice = Invoice::factory()->create();
        $comment = Comment::factory()->for($invoice)->create();

        $invoice->delete();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function deleting_comment_cascades_to_replies()
    {
        $parent = Comment::factory()->create();
        $reply = Comment::factory()->reply($parent)->create();

        $parent->delete();

        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    }

    /** @test */
    public function deleting_property_removes_tag_associations()
    {
        $property = Property::factory()->create();
        $tag = Tag::factory()->create(['tenant_id' => $property->tenant_id]);
        
        $property->attachTags([$tag]);
        
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_id' => $property->id,
            'taggable_type' => Property::class,
        ]);

        $property->delete();

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->id,
            'taggable_id' => $property->id,
        ]);
    }

    /** @test */
    public function soft_deleted_comments_can_be_restored()
    {
        $comment = Comment::factory()->create();
        
        $comment->delete();
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);

        $comment->restore();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'deleted_at' => null]);
    }
}
```

---

## Performance Tests

```php
// tests/Feature/RelationshipPerformanceTest.php
<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Comment;
use App\Models\Property;
use App\Models\Tag;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RelationshipPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function eager_loading_prevents_n_plus_1_queries()
    {
        Invoice::factory()
            ->count(10)
            ->has(Comment::factory()->count(3))
            ->create();

        DB::enableQueryLog();

        // Without eager loading - N+1 problem
        $invoices = Invoice::all();
        foreach ($invoices as $invoice) {
            $invoice->comments->count();
        }
        
        $queriesWithoutEagerLoading = count(DB::getQueryLog());
        DB::flushQueryLog();

        // With eager loading
        $invoices = Invoice::with('comments')->get();
        foreach ($invoices as $invoice) {
            $invoice->comments->count();
        }
        
        $queriesWithEagerLoading = count(DB::getQueryLog());

        $this->assertLessThan($queriesWithoutEagerLoading, $queriesWithEagerLoading);
    }

    /** @test */
    public function with_count_is_more_efficient_than_loading_all_relations()
    {
        Property::factory()
            ->count(10)
            ->has(Comment::factory()->count(5))
            ->create();

        DB::enableQueryLog();

        // Loading all comments
        $properties = Property::with('comments')->get();
        foreach ($properties as $property) {
            $property->comments->count();
        }
        
        $queriesWithLoad = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Using withCount
        $properties = Property::withCount('comments')->get();
        foreach ($properties as $property) {
            $property->comments_count;
        }
        
        $queriesWithCount = count(DB::getQueryLog());

        $this->assertLessThanOrEqual($queriesWithLoad, $queriesWithCount);
    }
}
```

This comprehensive testing guide covers all aspects of testing the advanced relationship patterns in your application.

