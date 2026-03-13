# Diagnostic Utilities API Reference

## Overview

The diagnostic utilities provide programmatic access to environment validation and health checking functionality. These utilities can be integrated into monitoring systems, CI/CD pipelines, and automated deployment processes.

## Core Functions

### `displayEnvironmentInfo(): void`

Displays comprehensive PHP environment information including version, SAPI, memory limits, and execution settings.

**Parameters**: None

**Returns**: `void`

**Output**: Writes environment information to stdout

**Example**:
```php
displayEnvironmentInfo();
// Output:
// PHP works: 8.4.0
// Current directory: /var/www/html
// PHP SAPI: cli
// Memory limit: 512M
// Max execution time: 0s
```

**Use Cases**:
- Environment validation in deployment scripts
- System information gathering for support
- Configuration verification

---

### `testAutoloader(): bool`

Validates Composer autoloader functionality and loads the autoloader if successful.

**Parameters**: None

**Returns**: `bool` - `true` if autoloader loads successfully

**Throws**: 
- `RuntimeException` - If autoloader file is missing or invalid

**Example**:
```php
try {
    $success = testAutoloader();
    echo "Autoloader status: " . ($success ? 'OK' : 'FAILED');
} catch (RuntimeException $e) {
    echo "Autoloader error: " . $e->getMessage();
}
```

**Validation Checks**:
- Autoloader file existence (`vendor/autoload.php`)
- File readability and permissions
- Successful require_once execution

---

### `testLaravelBootstrap(): Illuminate\Foundation\Application`

Tests Laravel application bootstrap process and returns the application instance.

**Parameters**: None

**Returns**: `Illuminate\Foundation\Application` - The bootstrapped Laravel application

**Throws**: 
- `RuntimeException` - If bootstrap file is missing or bootstrap fails

**Example**:
```php
try {
    $app = testLaravelBootstrap();
    echo "Laravel version: " . $app->version();
} catch (RuntimeException $e) {
    echo "Bootstrap error: " . $e->getMessage();
}
```

**Validation Checks**:
- Bootstrap file existence (`bootstrap/app.php`)
- Application instance creation
- Instance type validation

---

### `runDiagnostics(): int`

Executes the complete diagnostic suite and returns an exit code for automation integration.

**Parameters**: None

**Returns**: `int` - Exit code indicating test results

**Exit Codes**:
- `0` - All diagnostics passed successfully
- `1` - PHP environment issues
- `2` - Autoloader or runtime failure  
- `3` - Laravel bootstrap failure

**Example**:
```php
$exitCode = runDiagnostics();

switch ($exitCode) {
    case 0:
        echo "All systems operational";
        break;
    case 1:
        echo "PHP environment issues detected";
        break;
    case 2:
        echo "Autoloader or runtime problems";
        break;
    case 3:
        echo "Laravel bootstrap failed";
        break;
}
```

## Integration Patterns

### Programmatic Usage

```php
<?php
// Include the diagnostic script
require_once 'test-php.php';

// Use individual functions
try {
    displayEnvironmentInfo();
    testAutoloader();
    $app = testLaravelBootstrap();
    
    // Additional application-specific tests
    if ($app->bound('config')) {
        echo "Configuration service available\n";
    }
    
} catch (Exception $e) {
    error_log("Diagnostic failure: " . $e->getMessage());
    exit(1);
}
```

### Monitoring Integration

```php
<?php
// Health check endpoint
class HealthCheckController extends Controller
{
    public function diagnostics(): JsonResponse
    {
        $results = [];
        
        try {
            // Capture output for analysis
            ob_start();
            displayEnvironmentInfo();
            $results['environment'] = ob_get_clean();
            
            $results['autoloader'] = testAutoloader();
            $results['application'] = testLaravelBootstrap() instanceof Application;
            
            return response()->json([
                'status' => 'healthy',
                'diagnostics' => $results,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    }
}
```

### CI/CD Integration

```yaml
# GitHub Actions example
- name: Environment Diagnostics
  run: |
    php -r "
    require_once 'test-php.php';
    exit(runDiagnostics());
    "
  
- name: Detailed Diagnostics
  if: failure()
  run: |
    php -r "
    require_once 'test-php.php';
    displayEnvironmentInfo();
    try {
        testAutoloader();
        testLaravelBootstrap();
    } catch (Exception \$e) {
        echo 'Error: ' . \$e->getMessage();
    }
    "
```

### Docker Health Checks

```dockerfile
# Dockerfile health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD php -r "require_once 'test-php.php'; exit(runDiagnostics());"
```

## Error Handling

### Exception Types

#### `RuntimeException`
Thrown for expected runtime issues that can be resolved:
- Missing autoloader file
- Missing bootstrap file
- Invalid application instance

#### `Throwable`
Catches all unexpected errors:
- PHP syntax errors
- Memory exhaustion
- Permission issues
- Corrupted files

### Error Response Format

```php
// Standard error output format
echo "❌ ERROR_TYPE: " . $exception->getMessage() . "\n";
echo "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n";

// For unexpected errors, include stack trace
if ($exception instanceof Throwable && !$exception instanceof RuntimeException) {
    echo "Stack trace:\n" . $exception->getTraceAsString() . "\n";
}
```

## Performance Considerations

### Execution Time
- **Basic diagnostics**: < 100ms
- **Full bootstrap**: < 500ms
- **With application validation**: < 1s

### Memory Usage
- **Minimal footprint**: ~2MB
- **With Laravel bootstrap**: ~8-16MB
- **Full application**: ~32-64MB

### Optimization Tips

1. **Selective Testing**: Use individual functions for targeted validation
2. **Output Buffering**: Capture output for programmatic analysis
3. **Error Suppression**: Use `@` operator for non-critical checks in production
4. **Timeout Handling**: Set appropriate timeouts for automated systems

```php
// Optimized for production monitoring
function quickHealthCheck(): bool
{
    try {
        // Skip verbose output in production
        ob_start();
        $result = runDiagnostics();
        ob_end_clean();
        
        return $result === 0;
    } catch (Exception $e) {
        return false;
    }
}
```

## Security Considerations

### Information Disclosure
- Environment information may reveal system details
- Use appropriate access controls for diagnostic endpoints
- Filter sensitive information in production environments

### File Access
- Scripts require read access to vendor/ and bootstrap/ directories
- Validate file paths to prevent directory traversal
- Use appropriate file permissions (644 for scripts, 755 for directories)

### Error Handling
- Avoid exposing sensitive system information in error messages
- Log detailed errors securely while providing generic user messages
- Implement rate limiting for diagnostic endpoints

## Related APIs

- [Laravel Application API](https://laravel.com/api/master/Illuminate/Foundation/Application.html)
- [Composer Autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading)
- [PHP Runtime Information](https://www.php.net/manual/en/function.phpinfo.php)

## Changelog

### v1.2.0 (2025-01-06)
- Added comprehensive API documentation
- Enhanced error handling with specific exception types
- Improved integration patterns and examples
- Added performance and security considerations

### v1.1.0 (2024-12-15)
- Added programmatic access to diagnostic functions
- Enhanced return values and error reporting
- Added monitoring integration examples

### v1.0.0 (2024-12-01)
- Initial API for diagnostic utilities
- Basic function signatures and usage patterns