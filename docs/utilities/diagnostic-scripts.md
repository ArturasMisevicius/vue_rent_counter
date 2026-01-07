# Laravel Diagnostic Scripts

## Overview

The Vilnius Utilities Billing Platform includes a comprehensive suite of diagnostic scripts for troubleshooting environment setup, deployment issues, and application bootstrap problems. These utilities are essential for validating the Laravel 12 multi-tenant application in development, staging, and production environments.

## Available Diagnostic Scripts

### Administrative Scripts

#### `assign-super-admin.php` - Super Admin Assignment
**Purpose**: Assigns super admin privileges to the latest created user.

**What it does**:
- Finds the most recently created user
- Creates or locates super admin role
- Assigns Spatie Permission role
- Updates UserRole enum field
- Provides comprehensive status output

**Usage**:
```bash
php assign-super-admin.php
```

**Expected Output**:
```
🔍 Found latest user: John Doe (john@example.com) - ID: 1
✅ Assigned 'super_admin' role to user John Doe
✅ Updated user role enum to SUPERADMIN
🎉 Super admin access granted successfully!
🔗 User can now access /admin panel with full privileges
⚠️  Remember: Super admin has unrestricted access to all tenants and data
```

**Exit Codes**:
- `0` - Super admin assigned successfully
- `1` - No users found or assignment failed

**Security Warning**: 🔴 **HIGH RISK** - Grants unrestricted access to all system data and tenants.

### Core Environment Tests

#### `test-php.php` - Basic Environment Validation
**Purpose**: Validates PHP environment and basic Laravel bootstrap functionality.

**What it tests**:
- PHP version and SAPI information
- Memory limits and execution time settings
- Current working directory
- Composer autoloader functionality
- Laravel application bootstrap process
- Basic application instance validation

**Usage**:
```bash
php test-php.php
```

**Expected Output**:
```
PHP works: 8.4.x
Current directory: /path/to/project
PHP SAPI: cli
Memory limit: 512M
Max execution time: 0s
Autoload works
Bootstrap works
Laravel version: 12.x
✅ All diagnostics passed successfully
```

**Exit Codes**:
- `0` - All tests passed
- `1` - PHP environment issues
- `2` - Autoloader or runtime failure
- `3` - Laravel bootstrap failure

#### `test-simple.php` - Laravel Services Validation
**Purpose**: Tests Laravel service container and provider registration.

**What it tests**:
- Application creation and service binding
- Configuration service registration
- Provider loading and registration
- Service container functionality
- Database configuration access

#### `test-web-access.php` - HTTP Request Handling
**Purpose**: Validates web request handling and routing functionality.

**What it tests**:
- HTTP kernel creation and request handling
- Route accessibility (home, login, admin)
- Response status codes and headers
- TenantContext service functionality
- Request/response lifecycle

#### `test-load-configuration.php` - Configuration Bootstrap
**Purpose**: Tests Laravel configuration loading and bootstrap process.

**What it tests**:
- LoadConfiguration bootstrap class
- Configuration service binding
- Config file loading and parsing
- Provider registration after configuration

## Usage Patterns

### Development Environment Setup
Use these scripts during initial development environment setup:

```bash
# 1. Basic environment validation
php test-php.php

# 2. Laravel services validation
php test-simple.php

# 3. Web access validation
php test-web-access.php
```

### Deployment Validation
Run diagnostic scripts after deployment to validate environment:

```bash
# Quick validation script
#!/bin/bash
echo "Running deployment diagnostics..."

php test-php.php || exit 1
echo "✅ PHP environment validated"

php test-simple.php || exit 1
echo "✅ Laravel services validated"

php test-web-access.php || exit 1
echo "✅ Web access validated"

echo "🎉 All deployment diagnostics passed!"
```

### CI/CD Integration
Include diagnostic scripts in CI/CD pipelines:

```yaml
# .github/workflows/deploy.yml
- name: Validate Environment
  run: |
    php test-php.php
    php test-simple.php
    php test-web-access.php
```

### Docker Container Validation
Use in Docker health checks:

```dockerfile
# Dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD php test-php.php || exit 1
```

## Troubleshooting Guide

### Common Issues and Solutions

#### Autoloader Not Found
**Error**: `Autoloader not found at: /path/vendor/autoload.php`

**Solutions**:
```bash
# Install Composer dependencies
composer install

# Verify Composer installation
composer --version

# Check file permissions
ls -la vendor/autoload.php
```

#### Bootstrap File Missing
**Error**: `Bootstrap file not found at: /path/bootstrap/app.php`

**Solutions**:
```bash
# Verify Laravel installation
php artisan --version

# Check bootstrap directory
ls -la bootstrap/

# Reinstall Laravel if necessary
composer create-project laravel/laravel .
```

#### Memory Limit Issues
**Error**: `Fatal error: Allowed memory size exhausted`

**Solutions**:
```bash
# Increase PHP memory limit
php -d memory_limit=512M test-php.php

# Update php.ini
echo "memory_limit = 512M" >> php.ini

# Check current limits
php -i | grep memory_limit
```

#### Permission Issues
**Error**: `Permission denied` or file access errors

**Solutions**:
```bash
# Fix file permissions
chmod +x test-php.php
chmod -R 755 bootstrap/
chmod -R 755 vendor/

# Fix ownership (if needed)
chown -R www-data:www-data .
```

## Integration with Laravel Application

### Service Provider Integration
The diagnostic scripts can be integrated with Laravel service providers for automated health checks:

```php
// app/Providers/DiagnosticsServiceProvider.php
class DiagnosticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('local')) {
            $this->commands([
                DiagnosticsCommand::class,
            ]);
        }
    }
}
```

### Artisan Command Integration
Create Artisan commands that use diagnostic functionality:

```php
// app/Console/Commands/DiagnosticsCommand.php
class DiagnosticsCommand extends Command
{
    protected $signature = 'app:diagnostics';
    
    public function handle(): int
    {
        $this->info('Running application diagnostics...');
        
        // Run diagnostic scripts programmatically
        $exitCode = 0;
        
        exec('php test-php.php', $output, $exitCode);
        
        if ($exitCode === 0) {
            $this->info('✅ All diagnostics passed');
        } else {
            $this->error('❌ Diagnostics failed');
        }
        
        return $exitCode;
    }
}
```

### Monitoring Integration
Integrate with monitoring systems for production health checks:

```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $diagnostics = [
            'php' => $this->checkPhp(),
            'laravel' => $this->checkLaravel(),
            'database' => $this->checkDatabase(),
        ];
        
        $healthy = collect($diagnostics)->every(fn($check) => $check['status'] === 'ok');
        
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $diagnostics,
            'timestamp' => now()->toISOString(),
        ], $healthy ? 200 : 503);
    }
}
```

## Best Practices

### Script Development
1. **Follow Laravel Conventions**: Use Laravel coding standards and patterns
2. **Proper Error Handling**: Implement comprehensive error handling with meaningful messages
3. **Exit Codes**: Use standard exit codes for automation integration
4. **Logging**: Include detailed logging for troubleshooting
5. **Documentation**: Maintain clear documentation for each script

### Environment Considerations
1. **Development**: Enable verbose output and detailed error messages
2. **Staging**: Include performance metrics and timing information
3. **Production**: Focus on essential checks with minimal output
4. **Docker**: Ensure scripts work in containerized environments

### Security Considerations
1. **No Sensitive Data**: Never include credentials or sensitive information
2. **File Permissions**: Ensure scripts have appropriate permissions
3. **Input Validation**: Validate any external inputs or parameters
4. **Error Disclosure**: Avoid exposing sensitive system information in errors

## Related Documentation

- [Laravel 12 Bootstrap Process](../laravel/bootstrap-process.md)
- [Multi-Tenant Architecture](../architecture/multi-tenancy.md)
- [Deployment Guide](../deployment/deployment-guide.md)
- [Troubleshooting Guide](../troubleshooting/common-issues.md)
- [Testing Standards](../testing/testing-standards.md)

## Changelog

### v1.2.0 (2025-01-06)
- Enhanced `test-php.php` with comprehensive documentation
- Added structured error handling and exit codes
- Improved environment information display
- Added function-based architecture for reusability

### v1.1.0 (2024-12-15)
- Added `test-web-access.php` for HTTP validation
- Enhanced error reporting across all scripts
- Added TenantContext service validation

### v1.0.0 (2024-12-01)
- Initial diagnostic script suite
- Basic PHP and Laravel bootstrap validation
- Configuration loading tests