<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantInitialization;

use App\Models\Organization;
use App\Models\UtilityService;
use App\Services\TenantInitialization\SlugGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SlugGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlugGeneratorService $slugGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->slugGenerator = new SlugGeneratorService();
    }

    public function test_generates_unique_slug_for_new_service(): void
    {
        $tenant = Organization::factory()->create();
        
        $slug = $this->slugGenerator->generateUniqueSlug('Electricity Service', $tenant->id);
        
        $this->assertEquals('electricity-service', $slug);
    }

    public function test_generates_unique_slug_with_counter_for_existing_service(): void
    {
        $tenant = Organization::factory()->create();
        
        // Create existing service with base slug
        UtilityService::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'electricity-service',
        ]);
        
        $slug = $this->slugGenerator->generateUniqueSlug('Electricity Service', $tenant->id);
        
        $this->assertEquals('electricity-service-1', $slug);
    }

    public function test_generates_multiple_unique_slugs_batch(): void
    {
        $tenant = Organization::factory()->create();
        $names = ['Electricity', 'Water', 'Gas'];
        
        $slugs = $this->slugGenerator->generateMultipleUniqueSlugsBatch($names, $tenant->id);
        
        $this->assertEquals([
            'Electricity' => 'electricity',
            'Water' => 'water',
            'Gas' => 'gas',
        ], $slugs);
    }

    public function test_validates_slug_format(): void
    {
        $this->assertTrue($this->slugGenerator->isValidSlug('valid-slug'));
        $this->assertTrue($this->slugGenerator->isValidSlug('another-valid-slug-123'));
        
        $this->assertFalse($this->slugGenerator->isValidSlug('Invalid Slug'));
        $this->assertFalse($this->slugGenerator->isValidSlug(''));
        $this->assertFalse($this->slugGenerator->isValidSlug('slug with spaces'));
    }

    public function test_generates_slug_with_custom_separator(): void
    {
        $slug = $this->slugGenerator->generateSlugWithSeparator('Test Service', '_');
        
        $this->assertEquals('test_service', $slug);
    }

    public function test_clears_slug_cache(): void
    {
        $tenant = Organization::factory()->create();
        
        // Generate a slug to populate cache
        $this->slugGenerator->generateUniqueSlug('Test Service', $tenant->id);
        
        // Clear cache should not throw exception
        $this->slugGenerator->clearSlugCache($tenant->id);
        
        $this->assertTrue(true); // Test passes if no exception
    }
}