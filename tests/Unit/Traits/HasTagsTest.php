<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use App\Models\Property;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for HasTags Trait
 *
 * Tests the tagging functionality when applied to models like Property.
 */
final class HasTagsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Property $property;
    private Tag $tag1;
    private Tag $tag2;
    private Tag $tag3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($this->user);

        $this->property = Property::factory()->create(['tenant_id' => 1]);
        
        $this->tag1 = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'High Priority',
            'slug' => 'high-priority',
        ]);
        
        $this->tag2 = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'Maintenance Required',
            'slug' => 'maintenance-required',
        ]);
        
        $this->tag3 = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'Luxury',
            'slug' => 'luxury',
        ]);
    }

    public function test_property_can_have_tags_relationship(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphToMany::class, $this->property->tags());
    }

    public function test_can_attach_single_tag_to_property(): void
    {
        $this->property->attachTags($this->tag1);

        $this->assertCount(1, $this->property->tags);
        $this->assertEquals($this->tag1->id, $this->property->tags->first()->id);
    }

    public function test_can_attach_multiple_tags_to_property(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $this->assertCount(2, $this->property->tags);
        $this->assertTrue($this->property->tags->contains($this->tag1));
        $this->assertTrue($this->property->tags->contains($this->tag2));
    }

    public function test_can_attach_tags_by_id_array(): void
    {
        $this->property->attachTags([$this->tag1->id, $this->tag2->id]);

        $this->assertCount(2, $this->property->tags);
        $this->assertTrue($this->property->tags->contains($this->tag1));
        $this->assertTrue($this->property->tags->contains($this->tag2));
    }

    public function test_can_attach_tags_by_slug_array(): void
    {
        $this->property->attachTags(['high-priority', 'luxury']);

        $this->assertCount(2, $this->property->tags);
        $this->assertTrue($this->property->tags->contains($this->tag1));
        $this->assertTrue($this->property->tags->contains($this->tag3));
    }

    public function test_attach_tags_does_not_create_duplicates(): void
    {
        $this->property->attachTags($this->tag1);
        $this->property->attachTags($this->tag1); // Attach same tag again

        $this->assertCount(1, $this->property->tags);
    }

    public function test_can_detach_specific_tags(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2, $this->tag3]);
        
        $this->property->detachTags([$this->tag1, $this->tag2]);

        $this->assertCount(1, $this->property->tags);
        $this->assertEquals($this->tag3->id, $this->property->tags->first()->id);
    }

    public function test_can_detach_all_tags(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2, $this->tag3]);
        
        $this->property->detachTags();

        $this->assertCount(0, $this->property->tags);
    }

    public function test_can_sync_tags(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);
        
        // Sync with different tags
        $this->property->syncTags([$this->tag2, $this->tag3]);

        $this->assertCount(2, $this->property->tags);
        $this->assertTrue($this->property->tags->contains($this->tag2));
        $this->assertTrue($this->property->tags->contains($this->tag3));
        $this->assertFalse($this->property->tags->contains($this->tag1));
    }

    public function test_has_tag_method_works_with_tag_object(): void
    {
        $this->property->attachTags($this->tag1);

        $this->assertTrue($this->property->hasTag($this->tag1));
        $this->assertFalse($this->property->hasTag($this->tag2));
    }

    public function test_has_tag_method_works_with_slug(): void
    {
        $this->property->attachTags($this->tag1);

        $this->assertTrue($this->property->hasTag('high-priority'));
        $this->assertFalse($this->property->hasTag('maintenance-required'));
    }

    public function test_has_any_tag_method(): void
    {
        $this->property->attachTags($this->tag1);

        $this->assertTrue($this->property->hasAnyTag([$this->tag1, $this->tag2]));
        $this->assertFalse($this->property->hasAnyTag([$this->tag2, $this->tag3]));
    }

    public function test_has_all_tags_method(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $this->assertTrue($this->property->hasAllTags([$this->tag1, $this->tag2]));
        $this->assertFalse($this->property->hasAllTags([$this->tag1, $this->tag2, $this->tag3]));
    }

    public function test_get_tag_names_attribute(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $tagNames = $this->property->tag_names;

        $this->assertIsArray($tagNames);
        $this->assertCount(2, $tagNames);
        $this->assertContains('High Priority', $tagNames);
        $this->assertContains('Maintenance Required', $tagNames);
    }

    public function test_scope_with_tag_using_tag_object(): void
    {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        
        $property1->attachTags($this->tag1);

        $properties = Property::withTag($this->tag1)->get();

        $this->assertCount(1, $properties);
        $this->assertEquals($property1->id, $properties->first()->id);
    }

    public function test_scope_with_tag_using_slug(): void
    {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        
        $property1->attachTags($this->tag1);

        $properties = Property::withTag('high-priority')->get();

        $this->assertCount(1, $properties);
        $this->assertEquals($property1->id, $properties->first()->id);
    }

    public function test_scope_with_any_tag(): void
    {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        $property3 = Property::factory()->create(['tenant_id' => 1]);
        
        $property1->attachTags($this->tag1);
        $property2->attachTags($this->tag2);

        $properties = Property::withAnyTag([$this->tag1, $this->tag2])->get();

        $this->assertCount(2, $properties);
        $this->assertTrue($properties->contains($property1));
        $this->assertTrue($properties->contains($property2));
        $this->assertFalse($properties->contains($property3));
    }

    public function test_scope_with_all_tags(): void
    {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        $property3 = Property::factory()->create(['tenant_id' => 1]);
        
        $property1->attachTags([$this->tag1, $this->tag2]);
        $property2->attachTags($this->tag1);
        $property3->attachTags([$this->tag1, $this->tag2, $this->tag3]);

        $properties = Property::withAllTags([$this->tag1, $this->tag2])->get();

        $this->assertCount(2, $properties);
        $this->assertTrue($properties->contains($property1));
        $this->assertFalse($properties->contains($property2));
        $this->assertTrue($properties->contains($property3));
    }

    public function test_tagged_by_is_recorded_when_attaching_tags(): void
    {
        $this->property->attachTags($this->tag1, $this->user->id);

        $pivot = $this->property->tags()->where('tag_id', $this->tag1->id)->first()->pivot;
        
        $this->assertEquals($this->user->id, $pivot->tagged_by);
    }

    public function test_tagged_by_defaults_to_current_user(): void
    {
        $this->property->attachTags($this->tag1);

        $pivot = $this->property->tags()->where('tag_id', $this->tag1->id)->first()->pivot;
        
        $this->assertEquals($this->user->id, $pivot->tagged_by);
    }

    public function test_tag_usage_count_is_updated_when_attaching(): void
    {
        $initialCount = $this->tag1->usage_count;
        
        $this->property->attachTags($this->tag1);
        
        $this->tag1->refresh();
        $this->assertEquals($initialCount + 1, $this->tag1->usage_count);
    }

    public function test_tag_usage_count_is_updated_when_detaching(): void
    {
        $this->property->attachTags($this->tag1);
        $this->tag1->refresh();
        $countAfterAttach = $this->tag1->usage_count;
        
        $this->property->detachTags($this->tag1);
        
        $this->tag1->refresh();
        $this->assertEquals($countAfterAttach - 1, $this->tag1->usage_count);
    }

    public function test_respects_tenant_isolation_in_tag_queries(): void
    {
        // Create property and tag for different tenant
        $otherUser = User::factory()->create(['tenant_id' => 2]);
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        $otherTag = Tag::factory()->create(['tenant_id' => 2, 'slug' => 'other-tag']);
        
        $this->actingAs($otherUser);
        $otherProperty->attachTags($otherTag);
        
        // Switch back to original user
        $this->actingAs($this->user);
        $this->property->attachTags($this->tag1);

        // Should only see properties from current tenant
        $properties = Property::withTag($this->tag1)->get();
        
        $this->assertCount(1, $properties);
        $this->assertEquals($this->property->id, $properties->first()->id);
    }
}