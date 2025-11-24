<?php

namespace Tests\Performance;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceBenchmark extends TestCase
{
    use RefreshDatabase;

    protected array $results = [];
    protected int $iterations = 5;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed test data
        Artisan::call('test:setup', ['--fresh' => true]);
        
        $this->results = [
            'benchmark_date' => now()->toDateString(),
            'git_branch' => $this->getGitBranch(),
            'framework_versions' => $this->getFrameworkVersions(),
            'response_times_ms' => [],
            'database_performance' => [],
            'memory_usage_mb' => [],
            'query_counts' => [],
        ];
    }

    public function test_dashboard_load_times(): void
    {
        // Superadmin dashboard
        $superadmin = User::where('role', 'superadmin')->first();
        $this->results['response_times_ms']['dashboard_superadmin'] = 
            $this->measureResponseTime(function () use ($superadmin) {
                $this->actingAs($superadmin)->get('/superadmin/dashboard');
            });

        // Admin dashboard
        $admin = User::where('role', 'admin')->first();
        $this->results['response_times_ms']['dashboard_admin'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/dashboard');
            });

        // Manager dashboard
        $manager = User::where('role', 'manager')->first();
        $this->results['response_times_ms']['dashboard_manager'] = 
            $this->measureResponseTime(function () use ($manager) {
                $this->actingAs($manager)->get('/manager/dashboard');
            });

        // Tenant dashboard
        $tenant = User::where('role', 'tenant')->first();
        $this->results['response_times_ms']['dashboard_tenant'] = 
            $this->measureResponseTime(function () use ($tenant) {
                $this->actingAs($tenant)->get('/tenant/dashboard');
            });

        $this->assertTrue(true);
    }

    public function test_resource_list_load_times(): void
    {
        $admin = User::where('role', 'admin')->first();

        // Properties list
        $this->results['response_times_ms']['resource_list_properties'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/properties');
            });

        // Buildings list
        $this->results['response_times_ms']['resource_list_buildings'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/buildings');
            });

        // Meters list
        $this->results['response_times_ms']['resource_list_meters'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/meters');
            });

        // Invoices list
        $this->results['response_times_ms']['resource_list_invoices'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/invoices');
            });

        $this->assertTrue(true);
    }

    public function test_invoice_generation_time(): void
    {
        $property = Property::with('meters')->first();
        
        $this->results['response_times_ms']['invoice_generation'] = 
            $this->measureResponseTime(function () use ($property) {
                app(\App\Services\BillingService::class)->generateInvoice(
                    $property,
                    now()->startOfMonth(),
                    now()->endOfMonth()
                );
            });

        $this->assertTrue(true);
    }

    public function test_report_generation_time(): void
    {
        $admin = User::where('role', 'admin')->first();
        
        $this->results['response_times_ms']['report_generation'] = 
            $this->measureResponseTime(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/reports/consumption');
            });

        $this->assertTrue(true);
    }

    public function test_database_query_performance(): void
    {
        // Tenant-scoped properties query
        $this->results['database_performance']['tenant_scoped_properties'] = 
            $this->measureQueryTime(function () {
                Property::where('tenant_id', 1)->get();
            });

        // Meter readings with meters
        $this->results['database_performance']['meter_readings_with_meters'] = 
            $this->measureQueryTime(function () {
                Meter::with('readings')->limit(10)->get();
            });

        // Invoice with items
        $this->results['database_performance']['invoice_with_items'] = 
            $this->measureQueryTime(function () {
                Invoice::with('items')->limit(10)->get();
            });

        // Building with properties
        $this->results['database_performance']['building_with_properties'] = 
            $this->measureQueryTime(function () {
                Building::with('properties')->limit(10)->get();
            });

        // Migration execution time
        $start = microtime(true);
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->results['database_performance']['migration_execution_seconds'] = 
            round(microtime(true) - $start, 2);

        // Seeder execution time
        $start = microtime(true);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\TestDatabaseSeeder']);
        $this->results['database_performance']['seeder_execution_seconds'] = 
            round(microtime(true) - $start, 2);

        $this->assertTrue(true);
    }

    public function test_memory_usage(): void
    {
        $admin = User::where('role', 'admin')->first();

        // Dashboard load
        $this->results['memory_usage_mb']['dashboard_load'] = 
            $this->measureMemoryUsage(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/dashboard');
            });

        // Resource list
        $this->results['memory_usage_mb']['resource_list'] = 
            $this->measureMemoryUsage(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/properties');
            });

        // Invoice generation
        $property = Property::with('meters')->first();
        $this->results['memory_usage_mb']['invoice_generation'] = 
            $this->measureMemoryUsage(function () use ($property) {
                app(\App\Services\BillingService::class)->generateInvoice(
                    $property,
                    now()->startOfMonth(),
                    now()->endOfMonth()
                );
            });

        // Report generation
        $this->results['memory_usage_mb']['report_generation'] = 
            $this->measureMemoryUsage(function () use ($admin) {
                $this->actingAs($admin)->get('/admin/reports/consumption');
            });

        // Batch operations
        $this->results['memory_usage_mb']['batch_operations'] = 
            $this->measureMemoryUsage(function () {
                Property::with('meters.readings')->limit(50)->get();
            });

        $this->assertTrue(true);
    }

    public function test_query_counts(): void
    {
        $admin = User::where('role', 'admin')->first();

        // Dashboard queries
        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/dashboard');
        $this->results['query_counts']['dashboard_admin'] = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Properties list queries
        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin/properties');
        $this->results['query_counts']['resource_list_properties'] = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertTrue(true);
    }

    protected function measureResponseTime(callable $callback): float
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);
            $callback();
            $times[] = (microtime(true) - $start) * 1000; // Convert to milliseconds
        }

        return round(array_sum($times) / count($times), 2);
    }

    protected function measureQueryTime(callable $callback): float
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            DB::enableQueryLog();
            $start = microtime(true);
            $callback();
            $times[] = (microtime(true) - $start) * 1000; // Convert to milliseconds
            DB::disableQueryLog();
        }

        return round(array_sum($times) / count($times), 2);
    }

    protected function measureMemoryUsage(callable $callback): float
    {
        $usages = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $before = memory_get_usage(true);
            $callback();
            $after = memory_get_peak_usage(true);
            $usages[] = ($after - $before) / 1024 / 1024; // Convert to MB
        }

        return round(array_sum($usages) / count($usages), 2);
    }

    protected function getFrameworkVersions(): array
    {
        return [
            'laravel' => app()->version(),
            'filament' => class_exists(\Filament\Facades\Filament::class) 
                ? \Filament\Facades\Filament::getVersion() 
                : 'unknown',
            'php' => PHP_VERSION,
            'pest' => 'unknown',
            'phpunit' => 'unknown',
        ];
    }

    protected function getGitBranch(): string
    {
        try {
            return trim(shell_exec('git rev-parse --abbrev-ref HEAD') ?? 'unknown');
        } catch (\Exception $e) {
            return 'unknown';
        }
    }

    protected function tearDown(): void
    {
        // Save results to file after all tests complete
        if (!empty($this->results['response_times_ms'])) {
            $this->saveResults();
        }

        parent::tearDown();
    }

    protected function saveResults(): void
    {
        $outputPath = base_path('performance-post-upgrade.json');
        
        $finalResults = array_merge($this->results, [
            'notes' => [
                'Benchmarks run after Laravel 12 + Filament 4 upgrade',
                'Measurements taken with test database seeded via TestDatabaseSeeder',
                'Each metric is averaged over ' . $this->iterations . ' iterations',
                'Memory measurements show peak usage during operation',
                'Response times include full request/response cycle',
            ],
            'acceptance_criteria' => [
                'response_time_increase_max_percent' => 10,
                'database_query_increase_max_percent' => 15,
                'memory_usage_increase_max_percent' => 50,
                'test_suite_increase_max_percent' => 20,
            ],
        ]);

        file_put_contents(
            $outputPath,
            json_encode($finalResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
