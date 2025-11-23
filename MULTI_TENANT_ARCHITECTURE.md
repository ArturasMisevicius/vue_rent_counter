# Multi-Tenant Architecture for Vilnius Utilities Billing System

## Architecture Overview

**TENANCY TYPE**: Single database with tenant_id column (already implemented)

**APPLICATION DETAILS**:
- Expected tenants: 50-500 property management companies
- Tenant data isolation level: **Strict** (utility billing requires complete isolation)
- Shared resources: Users belong to single tenant (except superadmin role)
- Custom domains per tenant: No (single domain with session-based tenant context)
- Tenant-specific configuration: Email settings, invoice templates, billing rules, feature flags

**CURRENT STATE**: Your application already implements multi-tenancy with:
- `tenant_id` column on all scoped models
- `TenantScope` global scope for automatic filtering
- `HierarchicalScope` for hierarchical user management
- Session-based tenant context via `EnsureTenantContext` middleware

**TERMINOLOGY CLARIFICATION**:
- **Organization**: The multi-tenant entity (property management company) - uses `tenant_id` in database
- **Tenant**: An actual renter of a property (your existing `Tenant` model)
- Throughout this document, "tenant" in multi-tenancy context refers to Organization

---

## 1. Database Architecture

### Current Schema Enhancement

Your existing tables already have `tenant_id` columns. The architecture adds:

**New Migration**: `database/migrations/2025_11_23_000001_create_organizations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create organizations table (represents multi-tenant organizations)
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            
            // Basic info
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('email');
            $table->string('phone')->nullable();
            
            // Status management
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            
            // Subscription & limits
            $table->string('plan')->default('basic'); // basic, professional, enterprise
            $table->integer('max_properties')->default(100);
            $table->integer('max_users')->default(10);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Configuration (JSON)
            $table->json('settings')->nullable();
            $table->json('features')->nullable();
            
            // Localization
            $table->string('timezone')->default('Europe/Vilnius');
            $table->string('locale')->default('lt');
            $table->string('currency')->default('EUR');
            
            // Audit
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('plan');
            $table->index(['is_active', 'subscription_ends_at']);
        });

        // Activity logging for audit trail
        Schema::create('organization_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['organization_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });

        // Invitation system for onboarding users
        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('email');
            $table->string('role'); // manager, tenant
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->unsignedBigInteger('invited_by');
            $table->timestamps();
            
            $table->index(['organization_id', 'email']);
            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_activity_log');
        Schema::dropIfExists('organizations');
    }
};
```

**Key Design Decisions**:
- `tenant_id` in your existing tables maps to `organizations.id`
- Organizations table stores subscription, limits, and configuration
- Activity log provides audit trail for compliance
- Invitations enable secure user onboarding

---

## 2. Tenant Context Management

### Helper Function

Add to `app/helpers.php` (create if doesn't exist):

```php
<?php

use App\Models\Organization;
use App\Services\TenantContext;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant (organization)
     */
    function tenant(): ?Organization
    {
        return TenantContext::get();
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Get the current tenant ID
     */
    function tenant_id(): ?int
    {
        return TenantContext::id();
    }
}
```

Register in `composer.json`:

```json
{
    "autoload": {
        "files": [
            "app/helpers.php"
        ]
    }
}
```

Run: `composer dump-autoload`

### TenantContext Service

Already created at `app/Services/TenantContext.php`. Usage:

```php
// Get current tenant
$tenant = tenant();
$tenantId = tenant_id();

// Check if tenant context exists
if (TenantContext::has()) {
    // ...
}

// Set tenant (for superadmin switching)
TenantContext::set($organizationId);

// Execute within different tenant context
TenantContext::within($organizationId, function() {
    // Code here runs with different tenant
});

// Clear tenant context
TenantContext::clear();
```

---

## 3. Enhanced Middleware

Update `app/Http/Middleware/EnsureTenantContext.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use App\Models\OrganizationActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Initialize tenant context from session or user
        TenantContext::initialize();

        // Handle authenticated users
        if (auth()->check()) {
            $user = auth()->user();

            // Superadmin can operate without tenant context
            if ($user->isSuperadmin()) {
                if (!TenantContext::has()) {
                    \Log::info('Superadmin accessing without tenant context', [
                        'user_id' => $user->id,
                        'url' => $request->url(),
                    ]);
                }
                return $next($request);
            }

            // Regular users must have tenant
            if (!TenantContext::has() && $user->tenant_id) {
                TenantContext::set($user->tenant_id);
            }

            if (!TenantContext::has()) {
                abort(403, 'No tenant context available.');
            }

            // Validate tenant is active
            $tenant = TenantContext::get();
            if (!$tenant->isActive()) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Your organization has been suspended.');
            }

            // Log write operations for audit
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                OrganizationActivityLog::log(
                    action: $request->method() . ' ' . $request->path(),
                    metadata: ['route' => $request->route()?->getName()]
                );
            }
        }

        return $next($request);
    }
}
```

---

## 4. Model Scoping

### Your Existing TenantScope

Your `app/Scopes/TenantScope.php` already handles automatic filtering. Ensure all tenant-scoped models use it:

```php
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope);
}
```

**Models that SHOULD have TenantScope**:
- ✅ Building
- ✅ Property
- ✅ Tenant (renters)
- ✅ Meter
- ✅ MeterReading
- ✅ Invoice
- ✅ InvoiceItem
- ✅ User (except superadmin)

**Models that SHOULD NOT have TenantScope**:
- ❌ Provider (shared across all organizations)
- ❌ Tariff (shared across all organizations)
- ❌ Organization (the tenant itself)
- ❌ OrganizationActivityLog
- ❌ OrganizationInvitation

### Enhanced TenantScope

Update `app/Scopes/TenantScope.php` to use TenantContext:

```php
<?php

namespace App\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if no tenant context (e.g., during seeding or superadmin operations)
        if (!TenantContext::has()) {
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', TenantContext::id());
    }

    public function extend(Builder $builder): void
    {
        // Allow bypassing scope for superadmin
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        // Explicitly set tenant
        $builder->macro('forTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
        });
    }
}
```

Usage:

```php
// Normal query (automatically scoped)
$properties = Property::all();

// Bypass scope (superadmin only)
$allProperties = Property::withoutTenantScope()->get();

// Query specific tenant
$properties = Property::forTenant($organizationId)->get();
```

---

## 5. Automatic Tenant Assignment

### Trait for Auto-Assignment

Create `app/Traits/BelongsToTenant.php`:

```php
<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Services\TenantContext;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Apply global scope
        static::addGlobalScope(new TenantScope);

        // Auto-assign tenant_id on creation
        static::creating(function ($model) {
            if (!isset($model->tenant_id) && TenantContext::has()) {
                $model->tenant_id = TenantContext::id();
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'tenant_id');
    }
}
```

Update your models to use this trait instead of manually adding scope:

```php
class Property extends Model
{
    use HasFactory, BelongsToTenant;

    // Remove manual booted() method if it only adds TenantScope
}
```

---

## 6. Configuration Management

### Tenant-Specific Settings

Organizations store settings in JSON column. Access via:

```php
// Get setting
$invoicePrefix = tenant()->getSetting('invoice_prefix', 'INV');

// Set setting
tenant()->setSetting('invoice_prefix', 'BILL');

// Update multiple settings
tenant()->updateSettings([
    'invoice_prefix' => 'BILL',
    'auto_finalize_invoices' => true,
]);

// Check feature flag
if (tenant()->hasFeature('advanced_reporting')) {
    // Show advanced reports
}
```

### Default Settings Structure

```php
[
    'invoice_prefix' => 'INV',
    'invoice_number_start' => 1000,
    'email_from_name' => 'Organization Name',
    'email_from_address' => 'billing@example.com',
    'enable_notifications' => true,
    'auto_finalize_invoices' => false,
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
]
```

### Default Features by Plan

```php
'basic' => [
    'advanced_reporting' => false,
    'api_access' => false,
    'custom_branding' => false,
    'bulk_operations' => true,
    'export_data' => true,
    'audit_logs' => false,
],
'professional' => [
    'advanced_reporting' => true,
    'api_access' => false,
    'custom_branding' => false,
    'bulk_operations' => true,
    'export_data' => true,
    'audit_logs' => true,
],
'enterprise' => [
    'advanced_reporting' => true,
    'api_access' => true,
    'custom_branding' => true,
    'bulk_operations' => true,
    'export_data' => true,
    'audit_logs' => true,
],
```

---

## 7. File Storage Strategy

### Tenant-Specific Storage Paths

Update `config/filesystems.php`:

```php
'disks' => [
    'tenant' => [
        'driver' => 'local',
        'root' => storage_path('app/tenants'),
        'visibility' => 'private',
    ],
    
    'tenant_public' => [
        'driver' => 'local',
        'root' => storage_path('app/public/tenants'),
        'url' => env('APP_URL').'/storage/tenants',
        'visibility' => 'public',
    ],
],
```

### Storage Helper

Create `app/Services/TenantStorage.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class TenantStorage
{
    public static function disk(bool $public = false): \Illuminate\Filesystem\FilesystemAdapter
    {
        $disk = $public ? 'tenant_public' : 'tenant';
        return Storage::disk($disk);
    }

    public static function path(string $path = ''): string
    {
        if (!TenantContext::has()) {
            throw new \RuntimeException('No tenant context');
        }

        $tenantId = TenantContext::id();
        return $tenantId . '/' . ltrim($path, '/');
    }

    public static function put(string $path, $contents, bool $public = false): string
    {
        $fullPath = static::path($path);
        static::disk($public)->put($fullPath, $contents);
        return $fullPath;
    }

    public static function get(string $path, bool $public = false): ?string
    {
        $fullPath = static::path($path);
        return static::disk($public)->get($fullPath);
    }

    public static function delete(string $path, bool $public = false): bool
    {
        $fullPath = static::path($path);
        return static::disk($public)->delete($fullPath);
    }

    public static function url(string $path): string
    {
        $fullPath = static::path($path);
        return static::disk(true)->url($fullPath);
    }
}
```

Usage:

```php
// Store invoice PDF
TenantStorage::put('invoices/2024-001.pdf', $pdfContent);

// Store public logo
TenantStorage::put('logo.png', $imageContent, public: true);

// Get file
$content = TenantStorage::get('invoices/2024-001.pdf');

// Get public URL
$url = TenantStorage::url('logo.png');
```

---

## 8. Queue Job Handling

### Maintain Tenant Context in Jobs

Create `app/Traits/TenantAware.php`:

```php
<?php

namespace App\Traits;

use App\Services\TenantContext;

trait TenantAware
{
    public int $tenantId;

    public function __construct()
    {
        $this->tenantId = TenantContext::id() ?? throw new \RuntimeException('No tenant context');
    }

    public function handle(): void
    {
        TenantContext::within($this->tenantId, function () {
            $this->handleJob();
        });
    }

    abstract protected function handleJob(): void;
}
```

Usage in jobs:

```php
<?php

namespace App\Jobs;

use App\Traits\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAware;

    protected function handleJob(): void
    {
        // Job logic here - tenant context is automatically set
        $properties = Property::all(); // Automatically scoped to tenant
    }
}
```

---

## 9. Testing Strategy

### Test Case Base Class

Create `tests/TenantTestCase.php`:

```php
<?php

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test organization
        $this->tenant = Organization::factory()->create([
            'name' => 'Test Organization',
            'is_active' => true,
        ]);

        // Set tenant context
        TenantContext::set($this->tenant->id);

        // Create test user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'manager',
        ]);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    protected function createTenant(): Organization
    {
        return Organization::factory()->create();
    }

    protected function switchTenant(Organization $tenant): void
    {
        TenantContext::set($tenant->id);
    }
}
```

### Example Test

```php
<?php

use Tests\TenantTestCase;
use App\Models\Property;
use App\Models\Organization;

test('properties are isolated by tenant', function () {
    // Create property in current tenant
    $property1 = Property::factory()->create();

    // Create another tenant and property
    $tenant2 = Organization::factory()->create();
    $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);

    // Should only see property1
    expect(Property::count())->toBe(1);
    expect(Property::first()->id)->toBe($property1->id);

    // Switch tenant
    $this->switchTenant($tenant2);

    // Should only see property2
    expect(Property::count())->toBe(1);
    expect(Property::first()->id)->toBe($property2->id);
})->extends(TenantTestCase::class);
```

---

## 10. Tenant Onboarding Flow

### Registration Controller

Create `app/Http/Controllers/OrganizationRegistrationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OrganizationRegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register-organization');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'organization_email' => 'required|email|unique:organizations,email',
            'organization_phone' => 'nullable|string|max:20',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Create organization
            $organization = Organization::create([
                'name' => $validated['organization_name'],
                'email' => $validated['organization_email'],
                'phone' => $validated['organization_phone'],
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            // Create admin user
            $user = User::create([
                'tenant_id' => $organization->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'role' => 'admin',
            ]);

            // Update organization creator
            $organization->update(['created_by' => $user->id]);

            // Set tenant context and log in
            TenantContext::set($organization->id);
            auth()->login($user);

            DB::commit();

            return redirect()->route('dashboard')
                ->with('success', 'Welcome! Your 30-day trial has started.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }
}
```

### Invitation Acceptance

Create `app/Http/Controllers/OrganizationInvitationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OrganizationInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->pending()
            ->firstOrFail();

        return view('auth.accept-invitation', compact('invitation'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->pending()
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $invitation->organization_id,
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'role' => $invitation->role,
        ]);

        // Mark invitation as accepted
        $invitation->accept();

        // Set tenant context and log in
        TenantContext::set($invitation->organization_id);
        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to ' . $invitation->organization->name . '!');
    }
}
```

---

## 11. Security Considerations

### Preventing Tenant Data Leakage

**1. Always use TenantScope on models**
```php
// ✅ Good - automatically scoped
$properties = Property::all();

// ❌ Bad - bypasses scope
$properties = Property::withoutGlobalScope(TenantScope::class)->get();
```

**2. Validate tenant_id in form requests**
```php
public function rules(): array
{
    return [
        'property_id' => [
            'required',
            'exists:properties,id',
            function ($attribute, $value, $fail) {
                $property = Property::find($value);
                if ($property && $property->tenant_id !== tenant_id()) {
                    $fail('Invalid property selected.');
                }
            },
        ],
    ];
}
```

**3. Use policies for authorization**
```php
public function view(User $user, Property $property): bool
{
    // Policy automatically receives scoped model
    // But double-check for safety
    return $user->tenant_id === $property->tenant_id;
}
```

**4. Log suspicious activity**
```php
if ($user->tenant_id !== $resource->tenant_id) {
    \Log::warning('Tenant boundary violation attempt', [
        'user_id' => $user->id,
        'user_tenant' => $user->tenant_id,
        'resource_tenant' => $resource->tenant_id,
    ]);
    abort(403);
}
```

### Preventing Tenant Switching Attacks

**1. Validate session tenant matches user tenant**
```php
// In middleware
if (TenantContext::has() && auth()->check()) {
    $user = auth()->user();
    if (!$user->isSuperadmin() && $user->tenant_id !== TenantContext::id()) {
        TenantContext::clear();
        auth()->logout();
        abort(403, 'Tenant mismatch detected');
    }
}
```

**2. Regenerate session on tenant switch**
```php
public function switchTenant(int $tenantId)
{
    if (!auth()->user()->isSuperadmin()) {
        abort(403);
    }

    session()->regenerate();
    TenantContext::set($tenantId);
}
```

### Database Transaction Boundaries

**1. Ensure transactions respect tenant scope**
```php
DB::transaction(function () {
    // All queries here are automatically scoped
    $property = Property::create([...]);
    $meter = Meter::create(['property_id' => $property->id]);
});
```

**2. Validate tenant_id in transactions**
```php
DB::transaction(function () use ($propertyId, $meterId) {
    $property = Property::findOrFail($propertyId);
    $meter = Meter::findOrFail($meterId);
    
    // Both are automatically scoped, but validate relationship
    if ($meter->property_id !== $property->id) {
        throw new \Exception('Invalid property-meter relationship');
    }
    
    // Proceed with transaction
});
```

---

## 12. Migration Path

### Step 1: Create Organizations from Existing Tenants

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get unique tenant_ids from users table
        $tenantIds = DB::table('users')
            ->whereNotNull('tenant_id')
            ->distinct()
            ->pluck('tenant_id');

        foreach ($tenantIds as $tenantId) {
            // Get first admin user for this tenant
            $admin = DB::table('users')
                ->where('tenant_id', $tenantId)
                ->where('role', 'admin')
                ->first();

            if ($admin) {
                DB::table('organizations')->insert([
                    'id' => $tenantId,
                    'name' => 'Organization ' . $tenantId,
                    'slug' => 'org-' . $tenantId,
                    'email' => $admin->email,
                    'is_active' => true,
                    'plan' => 'basic',
                    'created_by' => $admin->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
```

### Step 2: Update Application Code

1. Add `BelongsToTenant` trait to models
2. Update middleware to use `TenantContext`
3. Add helper functions
4. Update tests to use `TenantTestCase`

### Step 3: Test Thoroughly

Run existing test suite to ensure no regressions:
```bash
php artisan test
```

---

## Summary

Your application already has solid multi-tenancy foundations. This architecture enhances it with:

✅ **Organization model** for managing tenant entities
✅ **TenantContext service** for centralized tenant management
✅ **Enhanced middleware** with activity logging and subscription checks
✅ **Tenant-specific storage** for file isolation
✅ **Queue job support** maintaining tenant context
✅ **Comprehensive testing** strategy
✅ **Security measures** preventing data leakage
✅ **Onboarding flow** for new organizations

The single-database approach is perfect for your use case (50-500 tenants) and integrates seamlessly with your existing SQLite setup and TenantScope implementation.
