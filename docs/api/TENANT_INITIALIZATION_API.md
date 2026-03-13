# Tenant Initialization API Documentation

## Overview

The Tenant Initialization API provides endpoints for initializing new tenants with default utility services and configurations. This API is primarily used by the admin panel and automated tenant onboarding processes.

## Authentication

All endpoints require authentication and appropriate permissions:

- **Authentication**: Bearer token or session-based
- **Authorization**: SuperAdmin or Admin role required
- **Tenant Context**: Operations are scoped to the authenticated user's tenant context

## Endpoints

### Initialize Tenant Services

Initialize a tenant with default utility service templates.

**Endpoint:** `POST /api/tenants/{tenant}/initialize-services`

**Parameters:**
- `tenant` (path) - Tenant ID or slug

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "services": ["electricity", "water", "heating", "gas"],
    "initialize_properties": true,
    "check_heating_compatibility": true
}
```

**Request Schema:**
```php
[
    'services' => 'array|nullable',
    'services.*' => 'string|in:electricity,water,heating,gas',
    'initialize_properties' => 'boolean',
    'check_heating_compatibility' => 'boolean'
]
```

**Success Response (201):**
```json
{
    "data": {
        "tenant_id": 123,
        "services_created": 4,
        "meter_configurations_created": 4,
        "properties_configured": 5,
        "total_configurations": 20,
        "heating_compatible": true,
        "services": {
            "electricity": {
                "id": 456,
                "name": "Electricity Service",
                "slug": "electricity-service",
                "service_type": "electricity",
                "pricing_model": "time_of_use",
                "is_active": true
            },
            "water": {
                "id": 457,
                "name": "Water Service", 
                "slug": "water-service",
                "service_type": "water",
                "pricing_model": "consumption_based",
                "is_active": true
            },
            "heating": {
                "id": 458,
                "name": "Heating Service",
                "slug": "heating-service", 
                "service_type": "heating",
                "pricing_model": "hybrid",
                "is_active": true
            },
            "gas": {
                "id": 459,
                "name": "Gas Service",
                "slug": "gas-service",
                "service_type": "gas", 
                "pricing_model": "tiered",
                "is_active": true
            }
        },
        "meter_configurations": {
            "electricity": {
                "meter_type": "smart",
                "supports_zones": true,
                "reading_structure": ["day", "night"],
                "validation_rules": {
                    "max_consumption": 10000,
                    "variance_threshold": 0.5,
                    "require_monotonic": true
                }
            }
            // ... other configurations
        }
    },
    "message": "Tenant services initialized successfully"
}
```

**Error Responses:**

**400 Bad Request:**
```json
{
    "error": "validation_failed",
    "message": "The given data was invalid.",
    "errors": {
        "services": ["The services field must be an array."],
        "services.0": ["The selected services.0 is invalid."]
    }
}
```

**403 Forbidden:**
```json
{
    "error": "insufficient_permissions",
    "message": "You do not have permission to initialize tenant services."
}
```

**404 Not Found:**
```json
{
    "error": "tenant_not_found", 
    "message": "The specified tenant could not be found."
}
```

**422 Unprocessable Entity:**
```json
{
    "error": "initialization_failed",
    "message": "Failed to initialize tenant services.",
    "details": {
        "tenant_id": 123,
        "failed_services": ["electricity"],
        "error_details": "Service creation failed: Invalid pricing model configuration"
    }
}
```

**500 Internal Server Error:**
```json
{
    "error": "server_error",
    "message": "An unexpected error occurred during tenant initialization.",
    "reference_id": "err_abc123"
}
```

### Get Tenant Initialization Status

Check the initialization status of a tenant's services.

**Endpoint:** `GET /api/tenants/{tenant}/initialization-status`

**Parameters:**
- `tenant` (path) - Tenant ID or slug

**Success Response (200):**
```json
{
    "data": {
        "tenant_id": 123,
        "is_initialized": true,
        "initialization_date": "2024-01-15T10:30:00Z",
        "services_count": 4,
        "properties_configured": 5,
        "heating_compatible": true,
        "services": [
            {
                "type": "electricity",
                "status": "active",
                "created_at": "2024-01-15T10:30:00Z"
            },
            {
                "type": "water", 
                "status": "active",
                "created_at": "2024-01-15T10:30:00Z"
            },
            {
                "type": "heating",
                "status": "active", 
                "created_at": "2024-01-15T10:30:00Z"
            },
            {
                "type": "gas",
                "status": "active",
                "created_at": "2024-01-15T10:30:00Z"
            }
        ],
        "missing_services": [],
        "configuration_issues": []
    }
}
```

### Reinitialize Tenant Services

Reinitialize services for a tenant (useful for adding new service types or fixing configuration issues).

**Endpoint:** `POST /api/tenants/{tenant}/reinitialize-services`

**Request Body:**
```json
{
    "services": ["gas"], 
    "force_recreate": false,
    "update_existing": true
}
```

**Request Schema:**
```php
[
    'services' => 'array|required',
    'services.*' => 'string|in:electricity,water,heating,gas',
    'force_recreate' => 'boolean',
    'update_existing' => 'boolean'
]
```

**Success Response (200):**
```json
{
    "data": {
        "tenant_id": 123,
        "services_updated": 1,
        "services_created": 0,
        "services_skipped": 3,
        "updated_services": ["gas"],
        "created_services": [],
        "skipped_services": ["electricity", "water", "heating"]
    },
    "message": "Tenant services reinitialized successfully"
}
```

## Controller Implementation

### TenantInitializationController

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitializeTenantServicesRequest;
use App\Http\Resources\TenantInitializationResource;
use App\Models\Organization;
use App\Services\TenantInitializationService;
use Illuminate\Http\JsonResponse;

final class TenantInitializationController extends Controller
{
    public function __construct(
        private readonly TenantInitializationService $initializationService,
    ) {}

    /**
     * Initialize tenant services.
     */
    public function initializeServices(
        InitializeTenantServicesRequest $request,
        Organization $tenant
    ): JsonResponse {
        $this->authorize('initialize', $tenant);

        try {
            $result = $this->initializationService->initializeUniversalServices($tenant);
            
            $propertyAssignments = null;
            if ($request->boolean('initialize_properties')) {
                $propertyAssignments = $this->initializationService
                    ->initializePropertyServiceAssignments($tenant, $result->utilityServices);
            }

            $heatingCompatible = true;
            if ($request->boolean('check_heating_compatibility')) {
                $heatingCompatible = $this->initializationService
                    ->ensureHeatingCompatibility($tenant);
            }

            return response()->json([
                'data' => new TenantInitializationResource(
                    $result,
                    $propertyAssignments,
                    $heatingCompatible
                ),
                'message' => 'Tenant services initialized successfully',
            ], 201);

        } catch (TenantInitializationException $e) {
            return response()->json([
                'error' => 'initialization_failed',
                'message' => 'Failed to initialize tenant services.',
                'details' => [
                    'tenant_id' => $tenant->id,
                    'error_details' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    /**
     * Get tenant initialization status.
     */
    public function getInitializationStatus(Organization $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $services = $tenant->utilityServices()
            ->select(['id', 'service_type_bridge', 'is_active', 'created_at'])
            ->get();

        $propertiesConfigured = $tenant->properties()
            ->whereHas('serviceConfigurations')
            ->count();

        $heatingCompatible = $this->initializationService
            ->ensureHeatingCompatibility($tenant);

        return response()->json([
            'data' => [
                'tenant_id' => $tenant->id,
                'is_initialized' => $services->isNotEmpty(),
                'initialization_date' => $services->min('created_at'),
                'services_count' => $services->count(),
                'properties_configured' => $propertiesConfigured,
                'heating_compatible' => $heatingCompatible,
                'services' => $services->map(fn($service) => [
                    'type' => $service->service_type_bridge->value,
                    'status' => $service->is_active ? 'active' : 'inactive',
                    'created_at' => $service->created_at->toISOString(),
                ]),
                'missing_services' => collect(['electricity', 'water', 'heating', 'gas'])
                    ->diff($services->pluck('service_type_bridge.value'))
                    ->values(),
                'configuration_issues' => [], // TODO: Implement configuration validation
            ],
        ]);
    }
}
```

## Form Request Validation

### InitializeTenantServicesRequest

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class InitializeTenantServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('initialize', $this->route('tenant'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'services' => 'array|nullable',
            'services.*' => 'string|in:electricity,water,heating,gas',
            'initialize_properties' => 'boolean',
            'check_heating_compatibility' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'services.array' => 'Services must be provided as an array.',
            'services.*.in' => 'Invalid service type. Must be one of: electricity, water, heating, gas.',
        ];
    }

    /**
     * Get validated services or default to all services.
     * 
     * @return array<string>
     */
    public function getServices(): array
    {
        return $this->validated('services', ['electricity', 'water', 'heating', 'gas']);
    }
}
```

## API Resource

### TenantInitializationResource

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\TenantInitialization\InitializationResult;
use App\Data\TenantInitialization\PropertyServiceAssignmentResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantInitializationResource extends JsonResource
{
    public function __construct(
        private readonly InitializationResult $result,
        private readonly ?PropertyServiceAssignmentResult $propertyAssignments = null,
        private readonly bool $heatingCompatible = true,
    ) {
        parent::__construct($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tenant_id' => $request->route('tenant')->id,
            'services_created' => $this->result->getServiceCount(),
            'meter_configurations_created' => $this->result->getMeterConfigurationCount(),
            'properties_configured' => $this->propertyAssignments?->getPropertyCount() ?? 0,
            'total_configurations' => $this->propertyAssignments?->getTotalConfigurationCount() ?? 0,
            'heating_compatible' => $this->heatingCompatible,
            'services' => $this->result->utilityServices->mapWithKeys(fn($service, $key) => [
                $key => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'service_type' => $service->service_type_bridge->value,
                    'pricing_model' => $service->default_pricing_model->value,
                    'is_active' => $service->is_active,
                ],
            ]),
            'meter_configurations' => $this->result->meterConfigurations,
        ];
    }
}
```

## Rate Limiting

API endpoints are rate limited to prevent abuse:

```php
// In RouteServiceProvider
Route::middleware(['api', 'auth:sanctum', 'throttle:tenant-init'])
    ->prefix('api/tenants')
    ->group(function () {
        Route::post('{tenant}/initialize-services', [TenantInitializationController::class, 'initializeServices']);
        Route::get('{tenant}/initialization-status', [TenantInitializationController::class, 'getInitializationStatus']);
    });
```

Rate limit configuration in `config/throttle.php`:
```php
'tenant-init' => [
    'limit' => 10,
    'decay' => 60, // 10 requests per minute
],
```

## Error Handling

### Global Exception Handler

```php
// In app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($exception instanceof TenantInitializationException) {
        return response()->json([
            'error' => 'initialization_failed',
            'message' => $exception->getMessage(),
        ], 422);
    }

    return parent::render($request, $exception);
}
```

## Testing

### API Tests

```php
public function test_can_initialize_tenant_services(): void
{
    $tenant = Organization::factory()->create();
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->postJson("/api/tenants/{$tenant->id}/initialize-services", [
            'services' => ['electricity', 'water'],
            'initialize_properties' => true,
            'check_heating_compatibility' => true,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'tenant_id',
                'services_created',
                'services' => [
                    'electricity' => ['id', 'name', 'service_type'],
                    'water' => ['id', 'name', 'service_type'],
                ],
            ],
            'message',
        ]);
}

public function test_requires_authorization_to_initialize_services(): void
{
    $tenant = Organization::factory()->create();
    $user = User::factory()->create(); // No admin role

    $response = $this->actingAs($user)
        ->postJson("/api/tenants/{$tenant->id}/initialize-services");

    $response->assertStatus(403);
}
```

## Security Considerations

### Authorization

- All endpoints require authentication
- Users can only initialize services for tenants they have access to
- SuperAdmin role can initialize services for any tenant
- Admin role can only initialize services for their own tenant

### Input Validation

- All input is validated using Form Requests
- Service types are restricted to allowed values
- Boolean flags are properly validated

### Rate Limiting

- Initialization endpoints are rate limited to prevent abuse
- Different limits for different user roles

### Audit Logging

- All initialization operations are logged with full context
- Failed attempts are logged with error details
- User actions are tracked for security auditing

## Related Documentation

- [TenantInitializationService Documentation](../services/TENANT_INITIALIZATION_SERVICE.md)
- [API Authentication Guide](API_AUTHENTICATION.md)
- [Multi-Tenant API Patterns](MULTI_TENANT_API_PATTERNS.md)
- [Error Handling Guide](ERROR_HANDLING.md)