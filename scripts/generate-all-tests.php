<?php

/**
 * Comprehensive Test Generation Script
 * 
 * This script generates tests for all components in the Vilnius Utilities Billing Platform
 * using the gsferro/generate-tests-easy package.
 * 
 * Usage: php scripts/generate-all-tests.php [--dry-run] [--verbose]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class TestGenerator
{
    protected bool $dryRun = false;
    protected bool $verbose = false;
    protected array $stats = [
        'generated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    public function __construct(array $options = [])
    {
        $this->dryRun = $options['dry-run'] ?? false;
        $this->verbose = $options['verbose'] ?? false;
    }

    public function run(): void
    {
        $this->output("🚀 Starting comprehensive test generation...\n");
        
        if ($this->dryRun) {
            $this->output("⚠️  DRY RUN MODE - No files will be created\n");
        }

        // Generate tests by category
        $this->generateModelTests();
        $this->generateControllerTests();
        $this->generateServiceTests();
        $this->generateFilamentResourceTests();
        $this->generatePolicyTests();
        $this->generateMiddlewareTests();
        $this->generateObserverTests();
        $this->generateValueObjectTests();
        
        $this->printSummary();
    }

    protected function generateModelTests(): void
    {
        $this->section("📦 Generating Model Tests");
        
        $models = [
            'App\\Models\\Building',
            'App\\Models\\Property',
            'App\\Models\\Meter',
            'App\\Models\\MeterReading',
            'App\\Models\\Invoice',
            'App\\Models\\InvoiceItem',
            'App\\Models\\Tariff',
            'App\\Models\\Provider',
            'App\\Models\\User',
            'App\\Models\\Tenant',
            'App\\Models\\Organization',
            'App\\Models\\Subscription',
            'App\\Models\\Faq',
            'App\\Models\\MeterReadingAudit',
        ];

        foreach ($models as $model) {
            $this->generateTest($model, 'model');
        }
    }

    protected function generateControllerTests(): void
    {
        $this->section("🎮 Generating Controller Tests");
        
        $controllers = [
            // Superadmin Controllers
            'App\\Http\\Controllers\\Superadmin\\DashboardController',
            'App\\Http\\Controllers\\Superadmin\\OrganizationController',
            'App\\Http\\Controllers\\Superadmin\\SubscriptionController',
            'App\\Http\\Controllers\\Superadmin\\TenantSwitchController',
            
            // Manager Controllers
            'App\\Http\\Controllers\\Manager\\DashboardController',
            'App\\Http\\Controllers\\Manager\\PropertyController',
            'App\\Http\\Controllers\\Manager\\BuildingController',
            'App\\Http\\Controllers\\Manager\\MeterController',
            'App\\Http\\Controllers\\Manager\\MeterReadingController',
            'App\\Http\\Controllers\\Manager\\InvoiceController',
            'App\\Http\\Controllers\\Manager\\ReportController',
            
            // Tenant Controllers
            'App\\Http\\Controllers\\Tenant\\DashboardController',
            'App\\Http\\Controllers\\Tenant\\PropertyController',
            'App\\Http\\Controllers\\Tenant\\MeterReadingController',
            'App\\Http\\Controllers\\Tenant\\InvoiceController',
            'App\\Http\\Controllers\\Tenant\\ProfileController',
            
            // Shared Controllers
            'App\\Http\\Controllers\\LocaleController',
            'App\\Http\\Controllers\\FaqController',
        ];

        foreach ($controllers as $controller) {
            $this->generateTest($controller, 'controller');
        }
    }

    protected function generateServiceTests(): void
    {
        $this->section("⚙️  Generating Service Tests");
        
        $services = [
            'App\\Services\\BillingService',
            'App\\Services\\TariffResolver',
            'App\\Services\\GyvatukasCalculator',
            'App\\Services\\SubscriptionService',
            'App\\Services\\AccountManagementService',
            'App\\Services\\TenantContext',
            'App\\Services\\BillingCalculatorFactory',
        ];

        foreach ($services as $service) {
            $this->generateTest($service, 'service');
        }
    }

    protected function generateFilamentResourceTests(): void
    {
        $this->section("🎨 Generating Filament Resource Tests");
        
        $resources = [
            'App\\Filament\\Resources\\PropertyResource',
            'App\\Filament\\Resources\\BuildingResource',
            'App\\Filament\\Resources\\MeterResource',
            'App\\Filament\\Resources\\MeterReadingResource',
            'App\\Filament\\Resources\\InvoiceResource',
            'App\\Filament\\Resources\\TariffResource',
            'App\\Filament\\Resources\\ProviderResource',
            'App\\Filament\\Resources\\UserResource',
            'App\\Filament\\Resources\\SubscriptionResource',
            'App\\Filament\\Resources\\FaqResource',
        ];

        foreach ($resources as $resource) {
            $this->generateTest($resource, 'filament');
        }
    }

    protected function generatePolicyTests(): void
    {
        $this->section("🔒 Generating Policy Tests");
        
        $policies = [
            'App\\Policies\\PropertyPolicy',
            'App\\Policies\\BuildingPolicy',
            'App\\Policies\\MeterPolicy',
            'App\\Policies\\MeterReadingPolicy',
            'App\\Policies\\InvoicePolicy',
            'App\\Policies\\TariffPolicy',
            'App\\Policies\\ProviderPolicy',
            'App\\Policies\\UserPolicy',
            'App\\Policies\\SubscriptionPolicy',
        ];

        foreach ($policies as $policy) {
            $this->generateTest($policy, 'policy');
        }
    }

    protected function generateMiddlewareTests(): void
    {
        $this->section("🛡️  Generating Middleware Tests");
        
        $middleware = [
            'App\\Http\\Middleware\\EnsureTenantContext',
            'App\\Http\\Middleware\\SecurityHeaders',
            'App\\Http\\Middleware\\SetLocale',
            'App\\Http\\Middleware\\CheckSubscription',
        ];

        foreach ($middleware as $class) {
            $this->generateTest($class, 'middleware');
        }
    }

    protected function generateObserverTests(): void
    {
        $this->section("👁️  Generating Observer Tests");
        
        $observers = [
            'App\\Observers\\MeterReadingObserver',
            'App\\Observers\\FaqObserver',
        ];

        foreach ($observers as $observer) {
            $this->generateTest($observer, 'observer');
        }
    }

    protected function generateValueObjectTests(): void
    {
        $this->section("💎 Generating Value Object Tests");
        
        $valueObjects = [
            'App\\ValueObjects\\InvoiceItemData',
            'App\\ValueObjects\\BillingPeriod',
            'App\\ValueObjects\\TimeRange',
            'App\\ValueObjects\\ConsumptionData',
        ];

        foreach ($valueObjects as $valueObject) {
            $this->generateTest($valueObject, 'value-object');
        }
    }

    protected function generateTest(string $class, string $type): void
    {
        $className = class_basename($class);
        
        if ($this->verbose) {
            $this->output("  Generating test for {$className}...");
        }

        if ($this->dryRun) {
            $this->stats['generated']++;
            if ($this->verbose) {
                $this->output(" ✓ (dry run)\n");
            }
            return;
        }

        try {
            // Use the test generation service
            $service = app(\App\Services\Testing\TestGenerationService::class);
            
            $result = $service->generateTests($class, $type);
            
            // Write the generated test file
            $directory = dirname($result['path']);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            file_put_contents($result['path'], $result['content']);
            
            $this->stats['generated']++;
            if ($this->verbose) {
                $this->output(" ✓\n");
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            if ($this->verbose) {
                $this->output(" ✗ Error: {$e->getMessage()}\n");
            }
        }
    }

    protected function section(string $title): void
    {
        $this->output("\n{$title}\n");
        $this->output(str_repeat("─", strlen($title)) . "\n");
    }

    protected function output(string $message): void
    {
        echo $message;
    }

    protected function printSummary(): void
    {
        $this->output("\n");
        $this->output("═══════════════════════════════════════════════════════════\n");
        $this->output("                    GENERATION SUMMARY                     \n");
        $this->output("═══════════════════════════════════════════════════════════\n");
        $this->output("  ✓ Generated: {$this->stats['generated']}\n");
        $this->output("  ⊘ Skipped:   {$this->stats['skipped']}\n");
        $this->output("  ✗ Errors:    {$this->stats['errors']}\n");
        $this->output("═══════════════════════════════════════════════════════════\n");
        
        if ($this->dryRun) {
            $this->output("\n⚠️  This was a DRY RUN - no files were created\n");
            $this->output("Run without --dry-run to generate actual tests\n");
        } else {
            $this->output("\n✅ Test generation complete!\n");
            $this->output("\nNext steps:\n");
            $this->output("  1. Review generated tests in tests/ directory\n");
            $this->output("  2. Enhance tests with custom scenarios\n");
            $this->output("  3. Run tests: php artisan test\n");
            $this->output("  4. Check coverage: php artisan test --coverage\n");
        }
    }
}

// Parse command line arguments
$options = [];
foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry-run'] = true;
    }
    if ($arg === '--verbose' || $arg === '-v') {
        $options['verbose'] = true;
    }
}

// Run the generator
$generator = new TestGenerator($options);
$generator->run();
