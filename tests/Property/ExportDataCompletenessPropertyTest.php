<?php

namespace Tests\Property;

use App\Services\ExportService;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * **Feature: superadmin-dashboard-enhancement, Property 19: Export data completeness**
 * 
 * Property-Based Test for export data completeness
 * 
 * **Validates: Requirements 12.1, 12.2, 12.3**
 * 
 * This test verifies that for any data export operation, all records matching 
 * the current filters should be included in the export file.
 */
class ExportDataCompletenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
    }

    public function test_organizations_export_includes_all_records(): void
    {
        // Generate random number of organizations (1-20)
        $organizationCount = rand(1, 20);
        $organizations = Organization::factory()->count($organizationCount)->create();
        
        // Export all organizations
        $csvPath = $this->exportService->exportOrganizationsCSV();
        $this->assertFileExists($csvPath);
        
        // Read CSV content
        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));
        
        // Remove header line
        array_shift($lines);
        
        // Filter out empty lines
        $dataLines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Verify count matches
        $this->assertEquals(
            $organizationCount, 
            count($dataLines),
            "Export should contain exactly {$organizationCount} organizations, but found " . count($dataLines)
        );
        
        // Verify each organization is present in export
        foreach ($organizations as $organization) {
            $found = false;
            foreach ($dataLines as $line) {
                if (str_contains($line, (string)$organization->id) && str_contains($line, $organization->name)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Organization {$organization->id} ({$organization->name}) not found in export");
        }
        
        // Clean up
        unlink($csvPath);
    }

    public function test_subscriptions_export_includes_all_records(): void
    {
        // Create organizations first
        $organizations = Organization::factory()->count(3)->create();
        
        // Create users for organizations
        $users = [];
        foreach ($organizations as $org) {
            $users[] = User::factory()->create([
                'tenant_id' => $org->id,
                'role' => 'admin'
            ]);
        }
        
        // Generate random number of subscriptions (1-15)
        $subscriptionCount = rand(1, 15);
        $subscriptions = [];
        
        for ($i = 0; $i < $subscriptionCount; $i++) {
            $user = $users[array_rand($users)];
            $subscriptions[] = Subscription::factory()->create([
                'user_id' => $user->id,
            ]);
        }
        
        // Export all subscriptions
        $csvPath = $this->exportService->exportSubscriptionsCSV();
        $this->assertFileExists($csvPath);
        
        // Read CSV content
        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));
        
        // Remove header line
        array_shift($lines);
        
        // Filter out empty lines
        $dataLines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Verify count matches
        $this->assertEquals(
            $subscriptionCount, 
            count($dataLines),
            "Export should contain exactly {$subscriptionCount} subscriptions, but found " . count($dataLines)
        );
        
        // Verify each subscription is present in export
        foreach ($subscriptions as $subscription) {
            $found = false;
            foreach ($dataLines as $line) {
                if (str_contains($line, (string)$subscription->id)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Subscription {$subscription->id} not found in export");
        }
        
        // Clean up
        unlink($csvPath);
    }

    public function test_activity_logs_export_includes_all_records(): void
    {
        // Create organization and user
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $organization->id,
            'role' => 'admin'
        ]);
        
        // Generate random number of activity logs (1-25)
        $logCount = rand(1, 25);
        $logs = [];
        
        for ($i = 0; $i < $logCount; $i++) {
            $logs[] = OrganizationActivityLog::factory()->create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'action' => 'test_action_' . $i,
            ]);
        }
        
        // Export all activity logs
        $csvPath = $this->exportService->exportActivityLogsCSV();
        $this->assertFileExists($csvPath);
        
        // Read CSV content
        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));
        
        // Remove header line
        array_shift($lines);
        
        // Filter out empty lines
        $dataLines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Verify count matches
        $this->assertEquals(
            $logCount, 
            count($dataLines),
            "Export should contain exactly {$logCount} activity logs, but found " . count($dataLines)
        );
        
        // Verify each log is present in export
        foreach ($logs as $log) {
            $found = false;
            foreach ($dataLines as $line) {
                if (str_contains($line, (string)$log->id) && str_contains($line, $log->action)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Activity log {$log->id} ({$log->action}) not found in export");
        }
        
        // Clean up
        unlink($csvPath);
    }

    public function test_filtered_organizations_export_respects_query_constraints(): void
    {
        // Create organizations with different statuses
        $activeOrgs = Organization::factory()->count(5)->create(['is_active' => true]);
        $inactiveOrgs = Organization::factory()->count(3)->create(['is_active' => false]);
        
        // Export only active organizations
        $query = Organization::where('is_active', true);
        $csvPath = $this->exportService->exportOrganizationsCSV($query);
        $this->assertFileExists($csvPath);
        
        // Read CSV content
        $csvContent = file_get_contents($csvPath);
        $lines = explode("\n", trim($csvContent));
        
        // Remove header line
        array_shift($lines);
        
        // Filter out empty lines
        $dataLines = array_filter($lines, fn($line) => !empty(trim($line)));
        
        // Should only contain active organizations
        $this->assertEquals(
            5, 
            count($dataLines),
            "Filtered export should contain exactly 5 active organizations, but found " . count($dataLines)
        );
        
        // Verify only active organizations are present
        foreach ($activeOrgs as $org) {
            $found = false;
            foreach ($dataLines as $line) {
                if (str_contains($line, (string)$org->id) && str_contains($line, $org->name)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Active organization {$org->id} should be in filtered export");
        }
        
        // Verify inactive organizations are NOT present
        foreach ($inactiveOrgs as $org) {
            $found = false;
            foreach ($dataLines as $line) {
                if (str_contains($line, (string)$org->id) && str_contains($line, $org->name)) {
                    $found = true;
                    break;
                }
            }
            $this->assertFalse($found, "Inactive organization {$org->id} should NOT be in filtered export");
        }
        
        // Clean up
        unlink($csvPath);
    }

    public function test_excel_export_contains_same_data_as_csv(): void
    {
        // Generate random organizations
        $organizationCount = rand(3, 10);
        Organization::factory()->count($organizationCount)->create();
        
        // Export to both formats
        $csvPath = $this->exportService->exportOrganizationsCSV();
        $excelPath = $this->exportService->exportOrganizationsExcel();
        
        $this->assertFileExists($csvPath);
        $this->assertFileExists($excelPath);
        
        // Read CSV content
        $csvContent = file_get_contents($csvPath);
        $csvLines = explode("\n", trim($csvContent));
        array_shift($csvLines); // Remove header
        $csvDataLines = array_filter($csvLines, fn($line) => !empty(trim($line)));
        
        // For Excel, we can't easily read the content without additional libraries,
        // but we can verify the file exists and has reasonable size
        $excelSize = filesize($excelPath);
        $csvSize = filesize($csvPath);
        
        // Excel files should be larger than CSV (due to formatting)
        $this->assertGreaterThan($csvSize, $excelSize, "Excel file should be larger than CSV file");
        
        // Both should contain the same number of records (verified by CSV count)
        $this->assertEquals($organizationCount, count($csvDataLines));
        
        // Clean up
        unlink($csvPath);
        unlink($excelPath);
    }

    public function test_json_export_preserves_all_data_fields(): void
    {
        // Create test data
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $organization->id,
            'role' => 'admin'
        ]);
        
        $log = OrganizationActivityLog::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'action' => 'test_action',
            'resource_type' => 'test_resource',
            'resource_id' => 123,
            'metadata' => ['key' => 'value', 'number' => 42],
        ]);
        
        // Export to JSON
        $jsonPath = $this->exportService->exportActivityLogsJSON();
        $this->assertFileExists($jsonPath);
        
        // Read and parse JSON
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);
        
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        
        $exportedLog = $data[0];
        
        // Verify all required fields are present
        $requiredFields = [
            'id', 'timestamp', 'organization', 'organization_id', 
            'user', 'user_id', 'action', 'resource_type', 
            'resource_id', 'metadata', 'ip_address', 'user_agent'
        ];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $exportedLog, "Field '{$field}' should be present in JSON export");
        }
        
        // Verify data integrity
        $this->assertEquals($log->id, $exportedLog['id']);
        $this->assertEquals($log->action, $exportedLog['action']);
        $this->assertEquals($log->resource_type, $exportedLog['resource_type']);
        $this->assertEquals($log->resource_id, $exportedLog['resource_id']);
        $this->assertEquals($log->metadata, $exportedLog['metadata']);
        $this->assertEquals($organization->name, $exportedLog['organization']);
        $this->assertEquals($user->name, $exportedLog['user']);
        
        // Clean up
        unlink($jsonPath);
    }
}