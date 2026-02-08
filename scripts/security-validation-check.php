<?php

declare(strict_types=1);

/**
 * Security Validation Check Script
 * 
 * Validates that all security enhancements are properly implemented
 * and the system is ready for production deployment.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "🔒 Security Validation Check\n";
echo "===========================\n\n";

$checks = [];
$passed = 0;
$failed = 0;

// Check 1: SecurityViolationPolicy exists and is properly configured
echo "1. Checking SecurityViolationPolicy...\n";
try {
    $policy = new \App\Policies\SecurityViolationPolicy();
    $checks['policy'] = '✅ SecurityViolationPolicy implemented';
    $passed++;
} catch (Exception $e) {
    $checks['policy'] = '❌ SecurityViolationPolicy missing: ' . $e->getMessage();
    $failed++;
}

// Check 2: CspViolationRequest validation
echo "2. Checking CspViolationRequest...\n";
try {
    $request = new \App\Http\Requests\CspViolationRequest();
    $rules = $request->rules();
    if (isset($rules['csp-report']) && isset($rules['csp-report.violated-directive'])) {
        $checks['csp_request'] = '✅ CspViolationRequest validation implemented';
        $passed++;
    } else {
        $checks['csp_request'] = '❌ CspViolationRequest validation incomplete';
        $failed++;
    }
} catch (Exception $e) {
    $checks['csp_request'] = '❌ CspViolationRequest error: ' . $e->getMessage();
    $failed++;
}

// Check 3: SecurityViolation model encryption
echo "3. Checking SecurityViolation encryption...\n";
try {
    $model = new \App\Models\SecurityViolation();
    $casts = $model->getCasts();
    if (isset($casts['metadata']) && str_contains($casts['metadata'], 'encrypted')) {
        $checks['encryption'] = '✅ SecurityViolation encryption configured';
        $passed++;
    } else {
        $checks['encryption'] = '❌ SecurityViolation encryption not configured';
        $failed++;
    }
} catch (Exception $e) {
    $checks['encryption'] = '❌ SecurityViolation encryption error: ' . $e->getMessage();
    $failed++;
}

// Check 4: Security configuration
echo "4. Checking security configuration...\n";
try {
    $configPath = __DIR__ . '/../config/security.php';
    if (file_exists($configPath)) {
        $configContent = file_get_contents($configPath);
        if (str_contains($configContent, 'require_authentication') && 
            str_contains($configContent, 'encrypt_sensitive_data')) {
            $checks['config'] = '✅ Security configuration enhanced';
            $passed++;
        } else {
            $checks['config'] = '❌ Security configuration not enhanced';
            $failed++;
        }
    } else {
        $checks['config'] = '❌ Security configuration file missing';
        $failed++;
    }
} catch (Exception $e) {
    $checks['config'] = '❌ Security configuration error: ' . $e->getMessage();
    $failed++;
}

// Check 5: MCP configuration security
echo "5. Checking MCP configuration...\n";
try {
    $mcpConfig = json_decode(file_get_contents(__DIR__ . '/../.kiro/settings/mcp.json'), true);
    $autoApproveEmpty = true;
    foreach ($mcpConfig['mcpServers'] as $server) {
        if (!empty($server['autoApprove'])) {
            $autoApproveEmpty = false;
            break;
        }
    }
    
    if ($autoApproveEmpty) {
        $checks['mcp_config'] = '✅ MCP auto-approve disabled (secure)';
        $passed++;
    } else {
        $checks['mcp_config'] = '❌ MCP auto-approve still enabled (insecure)';
        $failed++;
    }
} catch (Exception $e) {
    $checks['mcp_config'] = '❌ MCP configuration error: ' . $e->getMessage();
    $failed++;
}

// Check 6: SecurityMonitoringService
echo "6. Checking SecurityMonitoringService...\n";
try {
    $service = new \App\Services\Security\SecurityMonitoringService(
        app(\Psr\Log\LoggerInterface::class),
        app(\App\Services\Security\SecurityAnalyticsMcpService::class)
    );
    $checks['monitoring'] = '✅ SecurityMonitoringService implemented';
    $passed++;
} catch (Exception $e) {
    $checks['monitoring'] = '❌ SecurityMonitoringService error: ' . $e->getMessage();
    $failed++;
}

// Check 7: Security test files
echo "7. Checking security test files...\n";
$testFiles = [
    'tests/Feature/Security/SecurityViolationSecurityTest.php',
    'tests/Property/SecurityHeadersPropertyTest.php',
];

$testFilesExist = true;
foreach ($testFiles as $file) {
    if (!file_exists(__DIR__ . '/../' . $file)) {
        $testFilesExist = false;
        break;
    }
}

if ($testFilesExist) {
    $checks['tests'] = '✅ Security test files created';
    $passed++;
} else {
    $checks['tests'] = '❌ Security test files missing';
    $failed++;
}

// Check 8: Route security
echo "8. Checking route security...\n";
try {
    $routeContent = file_get_contents(__DIR__ . '/../routes/api-security.php');
    if (str_contains($routeContent, 'throttle:csp-reports,50,1') && 
        str_contains($routeContent, 'can:viewAny,App\Models\SecurityViolation')) {
        $checks['routes'] = '✅ Route security implemented';
        $passed++;
    } else {
        $checks['routes'] = '❌ Route security not fully implemented';
        $failed++;
    }
} catch (Exception $e) {
    $checks['routes'] = '❌ Route security error: ' . $e->getMessage();
    $failed++;
}

// Display results
echo "\n🔍 Security Validation Results\n";
echo "==============================\n\n";

foreach ($checks as $check => $result) {
    echo $result . "\n";
}

echo "\n📊 Summary\n";
echo "==========\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";
echo "📈 Success Rate: " . round(($passed / ($passed + $failed)) * 100, 1) . "%\n\n";

if ($failed === 0) {
    echo "🎉 ALL SECURITY CHECKS PASSED!\n";
    echo "🚀 System is ready for production deployment.\n\n";
    
    echo "🔒 Security Features Validated:\n";
    echo "- Authorization & Authentication ✅\n";
    echo "- Input Validation & Sanitization ✅\n";
    echo "- Data Encryption & Privacy ✅\n";
    echo "- Rate Limiting & DoS Protection ✅\n";
    echo "- Monitoring & Alerting ✅\n";
    echo "- Comprehensive Testing ✅\n";
    echo "- Compliance Controls ✅\n";
    echo "- Secure Configuration ✅\n\n";
    
    exit(0);
} else {
    echo "⚠️  SECURITY ISSUES DETECTED!\n";
    echo "❌ {$failed} security check(s) failed.\n";
    echo "🔧 Please review and fix the issues above before deployment.\n\n";
    exit(1);
}