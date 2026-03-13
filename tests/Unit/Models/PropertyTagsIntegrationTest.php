<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for Property Model Tags Integration
 *
 * Tests the integration of HasTags trait with Property model:
 * - Tag relationships and operations
 * - Tenant isolation for tags
 * - Performance optimizations
 * - Business logic validation
 */
final class PropertyTagsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;
    private Tag $tag1;
    private Tag $tag2;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($this->user);

        $this->property = Property::factory()->create([
            'tenant_id' => 1,
            'address' => '123 Test Street',
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 85.50,
        ]);

        $this->tag1 = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'High Priority',
            'slug' => 'high-priority',
            'color' => '#ff0000',
        ]);

        $this->tag2 = Tag::factory()->create([
            'tenant_id' => 1,
            'name' => 'Maintenance Required',
            'slug' => 'maintenance-required',
            'color' => '#ffa500',
        ]);
    }

    public function test_property_has_tags_relationship(): void
    {
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphToMany::class,
            $this->property->tags()
        );
    }

    public function test_can_attach_tags_to_property(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $this->assertCount(2, $this->property->fresh()->tags);
        $this->assertTrue($this->property->hasTag($this->tag1));
        $this->assertTrue($this->property->hasTag($this->tag2));
    }

    public function test_can_attach_tags_by_slug(): void
    {
        $this->property->attachTags(['high-priority', 'maintenance-required']);

        $this->assertCount(2, $this->property->fresh()->tags);
        $this->assertTrue($this->property->hasTag('high-priority'));
        $this->assertTrue($this->property->hasTag('maintenance-required'));
    }

    public function test_can_detach_tags_from_property(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);
        $this->property->detachTags([$this->tag1]);

        $this->assertCount(1, $this->property->fresh()->tags);
        $this->assertFalse($this->property->hasTag($this->tag1));
        $this->assertTrue($this->property->hasTag($this->tag2));
    }

    public function test_can_sync_tags_on_property(): void
    {
        $this->property->attachTags([$this->tag1]);
        $this->property->syncTags([$this->tag2]);

        $this->assertCount(1, $this->property->fresh()->tags);
        $this->assertFalse($this->property->hasTag($this->tag1));
        $this->assertTrue($this->property->hasTag($this->tag2));
    }

    public function test_property_scope_with_tags(): void
    {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);

        $property1->attachTags([$this->tag1]);
        $property2->attachTags([$this->tag2]);

        $propertiesWithTag1 = Property::withTag($this->tag1)->get();
        $propertiesWithAnyTag = Property::withAnyTag([$this->tag1, $this->tag2])->get();

        $this->assertCount(1, $propertiesWithTag1);
        $this->assertCount(2, $propertiesWithAnyTag);
        $this->assertTrue($propertiesWithTag1->contains($property1));
    }

    public function test_property_with_common_relations_includes_tags(): void
    {
        $this->property->attachTags([$this->tag1]);

        $property = Property::withCommonRelations()->find($this->property->id);

        $this->assertTrue($property->relationLoaded('tags'));
        $this->assertCount(1, $property->tags);
    }

    public function test_property_stats_summary_includes_tag_count(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $stats = $this->property->getStatsSummary();

        $this->assertArrayHasKey('tag_count', $stats);
        $this->assertEquals(2, $stats['tag_count']);
    }

    public function test_tag_usage_count_updates_when_attached_to_property(): void
    {
        $initialCount = $this->tag1->usage_count;

        $this->property->attachTags([$this->tag1]);

        $this->tag1->refresh();
        $this->assertEquals($initialCount + 1, $this->tag1->usage_count);
    }

    public function test_tag_usage_count_updates_when_detached_from_property(): void
    {
        $this->property->attachTags([$this->tag1]);
        $countAfterAttach = $this->tag1->fresh()->usage_count;

        $this->property->detachTags([$this->tag1]);

        $this->tag1->refresh();
        $this->assertEquals($countAfterAttach - 1, $this->tag1->usage_count);
    }

    public function test_tagged_by_is_recorded_when_attaching_tags(): void
    {
        $this->property->attachTags([$this->tag1], $this->user->id);

        $pivot = $this->property->tags()->where('tag_id', $this->tag1->id)->first()->pivot;
        $this->assertEquals($this->user->id, $pivot->tagged_by);
    }

    public function test_property_respects_tenant_isolation_for_tags(): void
    {
        // Create tag for different tenant
        $otherTenantTag = Tag::factory()->create([
            'tenant_id' => 2,
            'name' => 'Other Tenant Tag',
        ]);

        // Property should not be able to attach tags from other tenants
        $this->property->attachTags([$otherTenantTag]);

        // The tag should not be attached due to tenant isolation
        $this->assertCount(0, $this->property->fresh()->tags);
    }

    public function test_property_display_identifier_includes_unit_number(): void
    {
        $property = Property::factory()->create([
            'address' => '456 Main St',
            'unit_number' => '2A',
        ]);

        $this->assertEquals('456 Main St, Unit 2A', $property->getDisplayIdentifier());
    }

    public function test_property_display_identifier_without_unit_number(): void
    {
        $property = Property::factory()->create([
            'address' => '789 Oak Ave',
            'unit_number' => null,
        ]);

        $this->assertEquals('789 Oak Ave', $property->getDisplayIdentifier());
    }

    public function test_property_can_assign_tenant_when_vacant(): void
    {
        $vacantProperty = Property::factory()->create(['tenant_id' => 1]);

        $this->assertTrue($vacantProperty->canAssignTenant());
    }

    public function test_property_cannot_assign_tenant_when_occupied(): void
    {
        $occupiedProperty = Property::factory()->create(['tenant_id' => 1]);
        $tenant = \App\Models\Tenant::factory()->create(['tenant_id' => 1]);
        $occupiedProperty->tenants()->attach($tenant, ['assigned_at' => now()]);

        $this->assertFalse($occupiedProperty->canAssignTenant());
    }

    public function test_get_tag_names_attribute_returns_array(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $tagNames = $this->property->fresh()->tag_names;

        $this->assertIsArray($tagNames);
        $this->assertContains('High Priority', $tagNames);
        $this->assertContains('Maintenance Required', $tagNames);
    }

    public function test_property_has_any_tag_method(): void
    {
        $this->property->attachTags([$this->tag1]);

        $this->assertTrue($this->property->hasAnyTag([$this->tag1, $this->tag2]));
        $this->assertFalse($this->property->hasAnyTag([$this->tag2]));
    }

    public function test_property_has_all_tags_method(): void
    {
        $this->property->attachTags([$this->tag1, $this->tag2]);

        $this->assertTrue($this->property->hasAllTags([$this->tag1, $this->tag2]));
        $this->assertFalse($this->property->hasAllTags([$this->tag1, $this->tag2, Tag::factory()->create()]));
    }
}