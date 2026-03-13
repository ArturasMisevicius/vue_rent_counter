<?php

namespace Tests\Unit;

use App\Services\ExportService;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
    }

    public function test_can_get_export_stats(): void
    {
        $stats = $this->exportService->getExportStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('organizations_count', $stats);
        $this->assertArrayHasKey('subscriptions_count', $stats);
        $this->assertArrayHasKey('activity_logs_count', $stats);
        $this->assertArrayHasKey('last_export', $stats);
    }

    public function test_export_stats_returns_correct_counts(): void
    {
        // Create test data
        Organization::factory()->count(3)->create();
        
        $stats = $this->exportService->getExportStats();
        
        $this->assertEquals(3, $stats['organizations_count']);
        $this->assertIsInt($stats['subscriptions_count']);
        $this->assertIsInt($stats['activity_logs_count']);
    }

    public function test_can_export_organizations_csv(): void
    {
        // Create test organizations
        Organization::factory()->count(2)->create();
        
        $filePath = $this->exportService->exportOrganizationsCSV();
        
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('.csv', $filePath);
        
        // Clean up
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function test_can_export_organizations_excel(): void
    {
        // Create test organizations
        Organization::factory()->count(2)->create();
        
        $filePath = $this->exportService->exportOrganizationsExcel();
        
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('.xlsx', $filePath);
        
        // Clean up
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}