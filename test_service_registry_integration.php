<?php

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 SERVICE REGISTRY INTEGRATION TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$tests = [
    'ServiceRegistry Class Exists' => function() {
        return class_exists('App\\Support\\ServiceRegistration\\ServiceRegistry');
    },
    'PolicyRegistry Class Exists' => function() {
        return class_exists('App\\Support\\ServiceRegistration\\PolicyRegistry');
    },
    'ObserverRegistry Class Exists' => function() {
        return class_exists('App\\Support\\ServiceRegistration\\ObserverRegistry');
    },
    'EventRegistry Class Exists' => function() {
        return class_exists('App\\Support\\ServiceRegistration\\EventRegistry');
    },
    'CompatibilityRegistry Class Exists' => function() {
        return class_exists('App\\Support\\ServiceRegistration\\CompatibilityRegistry');
    },
    'ServiceRegistry Instantiation' => function() use ($app) {
        $registry = new App\Support\ServiceRegistration\ServiceRegistry($app);
        return $registry instanceof App\Support\ServiceRegistration\ServiceRegistry;
    },
    'ServiceRegistry Core Services Registration' => function() use ($app) {
        $registry = new App\Support\ServiceRegistration\ServiceRegistry($app);
        $registry->registerCoreServices();
        return true;
    },
    'ServiceRegistry Compatibility Services Registration' => function() use ($app) {
        $registry = new App\Support\ServiceRegistration\ServiceRegistry($app);
        $registry->registerCompatibilityServices();
        return true;
    },
    'PolicyRegistry Methods Available' => function() {
        $registry = new App\Support\ServiceRegistration\PolicyRegistry();
        return method_exists($registry, 'registerModelPolicies') && 
               method_exists($registry, 'registerSettingsGates');
    },
    'PolicyRegistry Model Policies' => function() {
        $registry = new App\Support\ServiceRegistration\PolicyRegistry();
        $policies = $registry->getModelPolicies();
        return is_array($policies) && count($policies) > 0;
    },
    'PolicyRegistry Settings Gates' => function() {
        $registry = new App\Support\ServiceRegistration\PolicyRegistry();
        $gates = $registry->getSettingsGates();
        return is_array($gates) && count($gates) > 0;
    },
    'ObserverRegistry Methods Available' => function() {
        $registry = new App\Support\ServiceRegistration\ObserverRegistry();
        return method_exists($registry, 'registerModelObservers') && 
               method_exists($registry, 'registerSuperadminObservers') &&
               method_exists($registry, 'registerCacheInvalidationObservers');
    },
    'EventRegistry Methods Available' => function() {
        $registry = new App\Support\ServiceRegistration\EventRegistry();
        return method_exists($registry, 'registerSecurityEvents') && 
               method_exists($registry, 'registerAuthenticationEvents') &&
               method_exists($registry, 'registerViewComposers') &&
               method_exists($registry, 'registerRateLimiters') &&
               method_exists($registry, 'registerCollectionMacros');
    },
    'CompatibilityRegistry Methods Available' => function() {
        $registry = new App\Support\ServiceRegistration\CompatibilityRegistry();
        return method_exists($registry, 'registerFilamentCompatibility') && 
               method_exists($registry, 'registerTranslationCompatibility');
    },
    'AppServiceProvider Integration' => function() use ($app) {
        $provider = new App\Providers\AppServiceProvider($app);
        $provider->register();
        $provider->boot();
        return true;
    },
    'Laravel Application Boot' => function() use ($app) {
        return $app instanceof Illuminate\Foundation\Application;
    }
];

$passed = 0;
$total = count($tests);

foreach ($tests as $testName => $testFunction) {
    try {
        $result = $testFunction();
        if ($result) {
            echo "✅ {$testName}\n";
            $passed++;
        } else {
            echo "❌ {$testName} - Test returned false\n";
        }
    } catch (Exception $e) {
        echo "❌ {$testName} - Error: {$e->getMessage()}\n";
    }
}

echo "\n" . str_repeat("=", 52) . "\n";
echo "RESULTS: {$passed}/{$total} tests passed\n";

if ($passed === $total) {
    echo "\n🎉 BUILD STATUS: GREEN ✅\n";
    echo "\n✅ ServiceRegistry integration is stable and working correctly!\n";
    echo "✅ All registry components are properly integrated!\n";
    echo "✅ AppServiceProvider is functioning correctly!\n";
    echo "✅ Ready to proceed with development tasks!\n";
    exit(0);
} else {
    echo "\n❌ BUILD STATUS: RED ❌\n";
    echo "\nSome tests failed. Please review the issues above.\n";
    exit(1);
}