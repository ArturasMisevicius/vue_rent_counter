<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Tests for Property Tagging System
 *
 * Tests the complete tagging workflow for properties including
 * multi-tenancy, performance, and real-world usage scenarios.
 */
final class PropertyTaggingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($this->user);
    }

    public function test_complete_property_tagging_workflow(): void
    {
        // Create properties
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        $property3 = Property::factory()->create(['tenant_id' => 1]);

        // Create tags
        $highPriorityTag = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'High Priority',
            'slug' => 'high-priority',
        ]);
        
        $maintenanceTag = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'Needs Maintenance',
            'slug' => 'needs-maintenance',
        ]);
        
        $luxuryTag = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'Luxury',
            'slug' => 'luxury',
        ]);

        // Tag properties with different combinations
        $property1->attachTags([$highPriorityTag, $maintenanceTag]);
        $property2->attachTags([$luxuryTag]);
        $property3->attachTags([$highPriorityTag, $luxuryTag]);

        // Test filtering by tags
        $highPriorityProperties = Property::withTag($highPriorityTag)->get();
        $this->assertCount(2, $highPriorityProperties);
        $this->assertTrue($highPriorityProperties->contains($property1));
        $this->assertTrue($highPriorityProperties->contains($property3));

        // Test filtering by multiple tags (any)
        $luxuryOrMaintenanceProperties = Property::withAnyTag([$luxuryTag, $maintenanceTag])->get();
        $this->assertCount(3, $luxuryOrMaintenanceProperties);

        // Test filtering by multiple tags (all)
        $highPriorityLuxuryProperties = Property::withAllTags([$highPriorityTag, $luxuryTag])->get();
        $this->assertCount(1, $highPriorityLuxuryProperties);
        $this->assertEquals($property3->id, $highPriorityLuxuryProperties->first()->id);

        // Test tag usage counts
        $highPriorityTag->refresh();
        $maintenanceTag->refresh();
        $luxuryTag->refresh();

        $this->assertEquals(2, $highPriorityTag->usage_count);
        $this->assertEquals(1, $maintenanceTag->usage_count);
        $this->assertEquals(2, $luxuryTag->usage_count);

        // Test removing tags
        $property1->detachTags([$maintenanceTag]);
        
        $maintenanceTag->refresh();
        $this->assertEquals(0, $maintenanceTag->usage_count);

        // Test syncing tags (replace all)
        $property2->syncTags([$highPriorityTag, $maintenanceTag]);
        
        $property2->refresh();
        $this->assertCount(2, $property2->tags);
        $this->assertTrue($property2->hasTag($highPriorityTag));
        $this->assertTrue($property2->hasTag($maintenanceTag));
        $this->assertFalse($property2->hasTag($luxuryTag));
    }

    public function test_property_tagging_respects_tenant_isolation(): void
    {
        // Create properties and tags for tenant 1
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $tag1 = Tag::factory()->create(['tenant_id' => 1, 'slug' => 'tenant1-tag']);
        
        // Create user and property for tenant 2
        $user2 = User::factory()->create(['tenant_id' => 2]);
        $property2 = Property::factory()->create(['tenant_id' => 2]);
        $tag2 = Tag::factory()->create(['tenant_id' => 2, 'slug' => 'tenant2-tag']);

        // Tag properties
        $property1->attachTags([$tag1]);
        
        $this->actingAs($user2);
        $property2->attachTags([$tag2]);

        // Switch back to tenant 1
        $this->actingAs($this->user);

        // Tenant 1 should only see their tagged properties
        $taggedProperties = Property::withTag($tag1)->get();
        $this->assertCount(1, $taggedProperties);
        $this->assertEquals($property1->id, $taggedProperties->first()->id);

        // Tenant 1 should not see tenant 2's tagged properties
        $otherTaggedProperties = Property::withTag('tenant2-tag')->get();
        $this->assertCount(0, $otherTaggedProperties);
    }

    public function test_property_scopes_work_with_tags(): void
    {
        // Create properties of different types
        $apartment = Property::factory()->create([
            'tenant_id' => 1,
            'type' => \App\Enums\PropertyType::APARTMENT,
        ]);
        
        $house = Property::factory()->create([
            'tenant_id' => 1,
            'type' => \App\Enums\PropertyType::HOUSE,
        ]);

        // Create tags
        $premiumTag = Tag::factory()->create([
            'tenant_id' => 1,
            'slug' => 'premium',
        ]);

        // Tag both properties
        $apartment->attachTags([$premiumTag]);
        $house->attachTags([$premiumTag]);

        // Test combining scopes with tag filtering
        $premiumApartments = Property::apartments()
            ->withTag($premiumTag)
            ->get();

        $this->assertCount(1, $premiumApartments);
        $this->assertEquals($apartment->id, $premiumApartments->first()->id);

        // Test residential properties with tags
        $premiumResidential = Property::residential()
            ->withTag($premiumTag)
            ->get();

        $this->assertCount(2, $premiumResidential);
    }

    public function test_bulk_tag_operations_performance(): void
    {
        // Create multiple properties
        $properties = Property::factory()->count(10)->create(['tenant_id' => 1]);
        
        // Create tags
        $tags = Tag::factory()->count(5)->create(['tenant_id' => 1]);

        // Measure time for bulk tagging operations
        $startTime = microtime(true);

        // Tag all properties with all tags
        foreach ($properties as $property) {
            $property->attachTags($tags);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete in reasonable time (less than 1 second for this small dataset)
        $this->assertLessThan(1.0, $executionTime);

        // Verify all tags were applied correctly
        foreach ($properties as $property) {
            $this->assertCount(5, $property->tags);
        }

        // Verify usage counts are correct
        foreach ($tags as $tag) {
            $tag->refresh();
            $this->assertEquals(10, $tag->usage_count);
        }
    }
}