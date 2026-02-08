# Tenant Initialization API Documentation

## Overview

The Tenant Initialization API provides endpoints for setting up new tenants with default utility service configurations and property-level assignments. This API is primarily used during tenant onboarding and property management workflows.

## Authentication & Authorization

### Required Permissions
- **SuperAdmin**: Full access to all tenant initialization operations
- **Admin**: Can initialize services for their own tenant
- **Manager**: Read-only access to initialization status

### Authentication Headers
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Endpoints

### Initialize Universal Services

Creates default utility services (electricity, water, heating, gas) for a new tenant.

```http
POST /api/tenants/{tenant}/initialize-services
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tenant` | integer | Yes | Tenant/Organization ID |

#### Request Body

```json
{
    "services": ["electricity", "water", "heating", "gas"],
    "regional_settings": {
        "locale": "lt",
        "timezone": "Europe/Vilnius",
        "currency": "EUR"
    },
    "options": {
        "skip_existing": true,
        "validate_compatibility": true
    }
}
```

#### Response

**Success (201 Created)**
```json
{
    "success": true,
    "data": {
        "initialization_result": {
            "services_created": 4,
            "meter_configurations_created": 4,
            "utility_services": {
                "electricity": {
                    "id": 123,
                    "name": "Electricity Service",
                    "slug": "electricity-service",
                    "unit_of_measurement": "kWh",
                    "default_pricing_model": "time_of_use"
                },
                "water": {
                    "id": 124,
                    "name": "Water Service",
                    "slug": "water-service",
                    "unit_of_measurement": "m³",
                    "default_pricing_model": "consumption_based"
                },
                "heating": {
                    "id": 125,
                    "name": "Heating Service",
                    "slug": "heating-service",
                    "unit_of_measurement": "kWh",
                    "default_pricing_model": "hybrid"
                },
                "gas": {
                    "id": 126,
                    "name": "Gas Service",
                    "slug": "gas-service",
                    "unit_of_measurement": "m³",
                    "default_pricing_model": "tiered_rates"
                }
            }
        }
    },
    "message": "Universal services initialized successfully"
}
```

**Error (422 Unprocessable Entity)**
```json
{
    "success": false,
    "error": {
        "code": "TENANT_INITIALIZATION_FAILED",
        "message": "Failed to initialize services for tenant",
        "details": {
            "tenant_id": 1,
            "operation": "service_creation",
            "failed_service": "electricity",
            "reason": "Invalid pricing model configuration"
        }
    }
}
```

### Initialize Property Service Assignments

Assigns utility services to existing properties with property-specific configurations.

```http
POST /api/tenants/{tenant}/properties/assign-services
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tenant` | integer | Yes | Tenant/Organization ID |

#### Request Body

```json
{
    "property_ids": [1, 2, 3],
    "service_types": ["electricity", "water", "heating", "gas"],
    "configuration_options": {
        "apply_property_type_adjustments": true,
        "apply_regional_defaults": true,
        "assign_providers": true
    }
}
```

#### Response

**Success (201 Created)**
```json
{
    "success": true,
    "data": {
        "assignment_result": {
            "properties_configured": 3,
            "total_configurations": 12,
            "configurations": {
                "1": {
                    "electricity": {
                        "id": 201,
                        "utility_service_id": 123,
                        "property_id": 1,
                        "pricing_model": "time_of_use",
                        "rate_schedule": {
                            "zone_rates": {
                                "day": 0.1547,
                                "night": 0.1047
                            }
                        },
                        "provider_id": 10,
                        "provider_name": "Lietuvos Energija"
                    },
                    "water": {
                        "id": 202,
                        "utility_service_id": 124,
                        "property_id": 1,
                        "pricing_model": "consumption_based",
                        "rate_schedule": {
                            "unit_rate": 1.89,
                            "connection_fee": 3.50
                        }
                    }
                }
            }
        }
    },
    "message": "Property service assignments completed successfully"
}
```

### Initialize Meter Configurations

Creates default meter configurations for properties based on their service assignments.

```http
POST /api/tenants/{tenant}/properties/initialize-meters
```

#### Request Body

```json
{
    "property_ids": [1, 2, 3],
    "service_configurations": {
        "1": ["electricity", "water", "heating"],
        "2": ["electricity", "water", "gas"],
        "3": ["electricity", "water", "heating", "gas"]
    }
}
```

#### Response

**Success (201 Created)**
```json
{
    "success": true,
    "data": {
        "meter_configurations": {
            "1": {
                "electricity": {
                    "meter_type": "electricity",
                    "supports_zones": true,
                    "reading_structure": {
                        "zones": ["day", "night"],
                        "required_fields": ["day_reading", "night_reading"]
                    },
                    "validation_rules": {
                        "max_consumption": 10000,
                        "variance_threshold": 0.5,
                        "require_monotonic": true
                    },
                    "requires_photo_verification": true
                },
                "water": {
                    "meter_type": "water",
                    "supports_zones": false,
                    "reading_structure": {
                        "zones": [],
                        "required_fields": ["total_reading"]
                    },
                    "validation_rules": {
                        "max_consumption": 1000,
                        "variance_threshold": 0.3,
                        "require_monotonic": true
                    },
                    "requires_photo_verification": false
                }
            }
        }
    },
    "message": "Meter configurations initialized successfully"
}
```

### Check Heating Compatibility

Validates that existing heating systems are compatible with the universal service framework.

```http
GET /api/tenants/{tenant}/heating-compatibility
```

#### Response

**Success (200 OK)**
```json
{
    "success": true,
    "data": {
        "is_compatible": true,
        "heating_service": {
            "id": 125,
            "service_type_bridge": "heating",
            "default_pricing_model": "hybrid",
            "supports_shared_distribution": true
        },
        "compatibility_checks": {
            "service_exists": true,
            "correct_pricing_model": true,
            "supports_distribution": true,
            "bridge_configured": true
        }
    },
    "message": "Heating service is compatible with universal framework"
}
```

**Incompatible (200 OK)**
```json
{
    "success": true,
    "data": {
        "is_compatible": false,
        "compatibility_checks": {
            "service_exists": false,
            "correct_pricing_model": false,
            "supports_distribution": false,
            "bridge_configured": false
        },
        "issues": [
            "No heating service found for tenant",
            "Heating service must use hybrid pricing model",
            "Heating service must support shared distribution"
        ]
    },
    "message": "Heating service requires configuration updates"
}
```

### Get Initialization Status

Retrieves the current initialization status for a tenant.

```http
GET /api/tenants/{tenant}/initialization-status
```

#### Response

```json
{
    "success": true,
    "data": {
        "tenant_id": 1,
        "initialization_status": {
            "services_initialized": true,
            "properties_configured": true,
            "meters_configured": true,
            "heating_compatible": true
        },
        "services": {
            "electricity": {
                "initialized": true,
                "service_id": 123,
                "properties_count": 5
            },
            "water": {
                "initialized": true,
                "service_id": 124,
                "properties_count": 5
            },
            "heating": {
                "initialized": true,
                "service_id": 125,
                "properties_count": 3
            },
            "gas": {
                "initialized": true,
                "service_id": 126,
                "properties_count": 2
            }
        },
        "summary": {
            "total_services": 4,
            "total_properties": 5,
            "total_configurations": 15,
            "last_updated": "2024-12-23T10:30:00Z"
        }
    }
}
```

## Error Codes

### Service Initialization Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `TENANT_NOT_FOUND` | 404 | Tenant does not exist |
| `TENANT_INVALID_DATA` | 422 | Tenant data validation failed |
| `SERVICE_CREATION_FAILED` | 422 | Failed to create utility service |
| `PROPERTY_ASSIGNMENT_FAILED` | 422 | Failed to assign services to properties |
| `HEATING_COMPATIBILITY_FAILED` | 422 | Heating compatibility check failed |
| `METER_CONFIGURATION_FAILED` | 422 | Failed to create meter configurations |

### Authorization Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `TENANT_ACCESS_DENIED` | 403 | Cannot access specified tenant |

### Validation Errors

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_FAILED` | 422 | Request validation failed |
| `INVALID_SERVICE_TYPE` | 422 | Unknown service type specified |
| `INVALID_PROPERTY_ID` | 422 | Property does not exist or not accessible |
| `DUPLICATE_INITIALIZATION` | 409 | Services already initialized |

## Rate Limiting

### Limits
- **Initialization endpoints**: 10 requests per minute per tenant
- **Status endpoints**: 60 requests per minute per user
- **Compatibility checks**: 30 requests per minute per tenant

### Headers
```http
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 9
X-RateLimit-Reset: 1640995200
```

## Webhooks

### Initialization Events

The API can send webhook notifications for initialization events:

#### Service Initialization Completed
```json
{
    "event": "tenant.services.initialized",
    "tenant_id": 1,
    "timestamp": "2024-12-23T10:30:00Z",
    "data": {
        "services_created": 4,
        "meter_configurations_created": 4
    }
}
```

#### Property Assignment Completed
```json
{
    "event": "tenant.properties.assigned",
    "tenant_id": 1,
    "timestamp": "2024-12-23T10:35:00Z",
    "data": {
        "properties_configured": 5,
        "total_configurations": 20
    }
}
```

#### Initialization Failed
```json
{
    "event": "tenant.initialization.failed",
    "tenant_id": 1,
    "timestamp": "2024-12-23T10:32:00Z",
    "data": {
        "operation": "service_creation",
        "error_code": "SERVICE_CREATION_FAILED",
        "error_message": "Failed to create electricity service"
    }
}
```

## SDK Examples

### PHP (Laravel)

```php
use App\Services\TenantInitializationService;

// Initialize services
$service = app(TenantInitializationService::class);
$result = $service->initializeUniversalServices($tenant);

// Assign to properties
$assignments = $service->initializePropertyServiceAssignments(
    $tenant, 
    $result->utilityServices
);

// Check heating compatibility
$compatible = $service->ensureHeatingCompatibility($tenant);
```

### JavaScript

```javascript
// Initialize services
const response = await fetch(`/api/tenants/${tenantId}/initialize-services`, {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        services: ['electricity', 'water', 'heating', 'gas'],
        regional_settings: {
            locale: 'lt',
            timezone: 'Europe/Vilnius',
            currency: 'EUR'
        }
    })
});

const result = await response.json();
```

### cURL

```bash
# Initialize services
curl -X POST "https://api.example.com/api/tenants/1/initialize-services" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "services": ["electricity", "water", "heating", "gas"],
    "regional_settings": {
      "locale": "lt",
      "timezone": "Europe/Vilnius",
      "currency": "EUR"
    }
  }'

# Check status
curl -X GET "https://api.example.com/api/tenants/1/initialization-status" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Testing

### Test Endpoints

Development and staging environments provide additional testing endpoints:

```http
POST /api/test/tenants/{tenant}/reset-initialization
DELETE /api/test/tenants/{tenant}/services
POST /api/test/tenants/{tenant}/simulate-failure
```

### Test Data

Use the following test tenant IDs in development:

- `tenant_id: 999` - Standard residential tenant
- `tenant_id: 998` - Commercial tenant with multiple properties
- `tenant_id: 997` - Lithuanian tenant with regional settings
- `tenant_id: 996` - Tenant with existing heating system

## Related Documentation

- [Tenant Initialization Service Documentation](../services/tenant-initialization-service.md)
- [Universal Utility Management Specification](../../.kiro/specs/universal-utility-management/)
- [Multi-Tenant API Architecture](../architecture/api-architecture.md)
- [Authentication & Authorization Guide](authentication.md)