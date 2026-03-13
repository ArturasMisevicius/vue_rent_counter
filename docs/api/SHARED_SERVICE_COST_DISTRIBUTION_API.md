# Shared Service Cost Distribution API

## Overview

This document describes the API interfaces and contracts for the shared service cost distribution system. The API provides programmatic access to cost distribution functionality for utility billing applications.

## Core Interfaces

### SharedServiceCostDistributor Contract

```php
namespace App\Contracts;

use App\Models\ServiceConfiguration;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\SharedServiceCostDistributionResult;
use Illuminate\Support\Collection;

interface SharedServiceCostDistributor
{
    /**
     * Distribute a total cost among properties according to configuration.
     *
     * @param ServiceConfiguration $serviceConfig Configuration with distribution method
     * @param Collection<int, \App\Models\Property> $properties Properties to distribute among
     * @param float $totalCost Total amount to distribute (must be >= 0)
     * @param BillingPeriod $billingPeriod Billing period for the distribution
     * @return SharedServiceCostDistributionResult Distribution results
     * 
     * @throws \InvalidArgumentException For invalid inputs
     * @throws \App\Exceptions\DistributionException For distribution errors
     */
    public function distributeCost(
        ServiceConfiguration $serviceConfig,
        Collection $properties,
        float $totalCost,
        BillingPeriod $billingPeriod
    ): SharedServiceCostDistributionResult;

    /**
     * Validate properties for the specified distribution method.
     *
     * @param ServiceConfiguration $serviceConfig Configuration to validate against
     * @param Collection<int, \App\Models\Property> $properties Properties to validate
     * @return array<string> List of validation error messages
     */
    public function validateProperties(
        ServiceConfiguration $serviceConfig,
        Collection $properties
    ): array;

    /**
     * Get supported distribution methods.
     *
     * @return array<\App\Enums\DistributionMethod> Supported methods
     */
    public function getSupportedMethods(): array;
}
```

## HTTP API Endpoints

### POST /api/v1/cost-distribution/distribute

Distribute costs among properties for a service configuration.

#### Request

```http
POST /api/v1/cost-distribution/distribute
Content-Type: application/json
Authorization: Bearer {token}

{
    "service_configuration_id": 123,
    "property_ids": [1, 2, 3, 4],
    "total_cost": 1500.00,
    "billing_period": {
        "start_date": "2024-03-01",
        "end_date": "2024-03-31"
    }
}
```

#### Request Schema

```json
{
    "type": "object",
    "required": ["service_configuration_id", "property_ids", "total_cost", "billing_period"],
    "properties": {
        "service_configuration_id": {
            "type": "integer",
            "minimum": 1,
            "description": "ID of the service configuration"
        },
        "property_ids": {
            "type": "array",
            "items": {
                "type": "integer",
                "minimum": 1
            },
            "minItems": 1,
            "maxItems": 1000,
            "description": "Array of property IDs to distribute cost among"
        },
        "total_cost": {
            "type": "number",
            "minimum": 0,
            "maximum": 999999.99,
            "description": "Total cost to distribute"
        },
        "billing_period": {
            "type": "object",
            "required": ["start_date", "end_date"],
            "properties": {
                "start_date": {
                    "type": "string",
                    "format": "date",
                    "description": "Billing period start date (YYYY-MM-DD)"
                },
                "end_date": {
                    "type": "string",
                    "format": "date",
                    "description": "Billing period end date (YYYY-MM-DD)"
                }
            }
        }
    }
}
```

#### Success Response (200 OK)

```json
{
    "success": true,
    "data": {
        "distributed_amounts": {
            "1": 375.00,
            "2": 375.00,
            "3": 375.00,
            "4": 375.00
        },
        "total_distributed": 1500.00,
        "statistics": {
            "property_count": 4,
            "average_allocation": 375.00,
            "min_allocation": 375.00,
            "max_allocation": 375.00,
            "is_balanced": true,
            "metadata": {
                "method": "equal",
                "amount_per_property": 375.00
            }
        }
    },
    "meta": {
        "distribution_method": "equal",
        "billing_period": {
            "start_date": "2024-03-01",
            "end_date": "2024-03-31",
            "days": 31,
            "label": "March 2024"
        },
        "processed_at": "2024-03-15T10:30:00Z"
    }
}
```

#### Error Responses

**400 Bad Request - Validation Error**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "total_cost": ["The total cost must be at least 0."],
            "property_ids": ["The property ids field must contain at least 1 items."]
        }
    }
}
```

**404 Not Found - Service Configuration**
```json
{
    "success": false,
    "error": {
        "code": "SERVICE_CONFIG_NOT_FOUND",
        "message": "Service configuration not found or not accessible.",
        "details": {
            "service_configuration_id": 123
        }
    }
}
```

**422 Unprocessable Entity - Distribution Error**
```json
{
    "success": false,
    "error": {
        "code": "DISTRIBUTION_ERROR",
        "message": "Properties validation failed: Property 2 missing or invalid area data",
        "details": {
            "distribution_method": "area",
            "validation_errors": [
                "Property 2 missing or invalid area data",
                "Property 3 missing or invalid area data"
            ]
        }
    }
}
```

### POST /api/v1/cost-distribution/validate

Validate properties for a distribution method without performing distribution.

#### Request

```http
POST /api/v1/cost-distribution/validate
Content-Type: application/json
Authorization: Bearer {token}

{
    "service_configuration_id": 123,
    "property_ids": [1, 2, 3, 4]
}
```

#### Success Response (200 OK)

```json
{
    "success": true,
    "data": {
        "valid": true,
        "errors": [],
        "distribution_method": "area",
        "property_count": 4
    }
}
```

#### Validation Error Response (200 OK)

```json
{
    "success": true,
    "data": {
        "valid": false,
        "errors": [
            "Property 2 missing or invalid area data",
            "Property 3 missing or invalid area data"
        ],
        "distribution_method": "area",
        "property_count": 4
    }
}
```

### GET /api/v1/cost-distribution/methods

Get supported distribution methods.

#### Response (200 OK)

```json
{
    "success": true,
    "data": {
        "methods": [
            {
                "value": "equal",
                "label": "Equal Distribution",
                "description": "Divide cost equally among all properties",
                "requires_area_data": false,
                "requires_consumption_data": false,
                "supports_custom_formulas": false
            },
            {
                "value": "area",
                "label": "Area-Based Distribution",
                "description": "Distribute cost proportionally based on property areas",
                "requires_area_data": true,
                "requires_consumption_data": false,
                "supports_custom_formulas": false,
                "supported_area_types": ["total_area", "heated_area", "commercial_area"]
            },
            {
                "value": "by_consumption",
                "label": "Consumption-Based Distribution",
                "description": "Distribute cost based on historical consumption",
                "requires_area_data": false,
                "requires_consumption_data": true,
                "supports_custom_formulas": false
            },
            {
                "value": "custom_formula",
                "label": "Custom Formula Distribution",
                "description": "Use mathematical expressions for distribution",
                "requires_area_data": false,
                "requires_consumption_data": false,
                "supports_custom_formulas": true,
                "available_variables": ["area", "consumption", "property_id"],
                "supported_operations": ["+", "-", "*", "/", "(", ")"],
                "supported_functions": ["min", "max", "abs", "round"]
            }
        ]
    }
}
```

## Authentication & Authorization

### Authentication

All API endpoints require authentication via Bearer token:

```http
Authorization: Bearer {access_token}
```

### Authorization Rules

1. **Tenant Scoping**: Users can only access service configurations and properties within their tenant
2. **Role-Based Access**: 
   - `admin`: Full access to all distribution operations
   - `manager`: Can distribute costs for assigned buildings/properties
   - `viewer`: Read-only access to distribution results

### Permission Checks

```php
// Service configuration access
Gate::authorize('view', $serviceConfiguration);

// Property access (all properties must be accessible)
foreach ($properties as $property) {
    Gate::authorize('view', $property);
}

// Distribution operation
Gate::authorize('distribute-costs', $serviceConfiguration);
```

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **Distribution endpoint**: 60 requests per minute per user
- **Validation endpoint**: 120 requests per minute per user
- **Methods endpoint**: 300 requests per minute per user

Rate limit headers are included in responses:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1710504600
```

## Error Handling

### Standard Error Format

All error responses follow a consistent format:

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": {
            "additional": "context information"
        }
    }
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 400 | Request validation failed |
| `UNAUTHORIZED` | 401 | Authentication required |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `SERVICE_CONFIG_NOT_FOUND` | 404 | Service configuration not found |
| `PROPERTY_NOT_FOUND` | 404 | One or more properties not found |
| `DISTRIBUTION_ERROR` | 422 | Distribution calculation failed |
| `FORMULA_ERROR` | 422 | Custom formula validation/evaluation failed |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `INTERNAL_ERROR` | 500 | Unexpected server error |

## SDK Examples

### PHP SDK Usage

```php
use App\Services\CostDistributionApiClient;

$client = new CostDistributionApiClient($accessToken);

// Distribute costs
$result = $client->distributeCost([
    'service_configuration_id' => 123,
    'property_ids' => [1, 2, 3, 4],
    'total_cost' => 1500.00,
    'billing_period' => [
        'start_date' => '2024-03-01',
        'end_date' => '2024-03-31',
    ],
]);

if ($result->isSuccess()) {
    $distributedAmounts = $result->getData()['distributed_amounts'];
    foreach ($distributedAmounts as $propertyId => $amount) {
        echo "Property {$propertyId}: €{$amount}\n";
    }
} else {
    echo "Error: " . $result->getError()['message'];
}
```

### JavaScript SDK Usage

```javascript
import { CostDistributionClient } from '@company/cost-distribution-sdk';

const client = new CostDistributionClient({ accessToken });

try {
    const result = await client.distributeCost({
        serviceConfigurationId: 123,
        propertyIds: [1, 2, 3, 4],
        totalCost: 1500.00,
        billingPeriod: {
            startDate: '2024-03-01',
            endDate: '2024-03-31'
        }
    });
    
    console.log('Distribution completed:', result.data.distributed_amounts);
} catch (error) {
    console.error('Distribution failed:', error.message);
}
```

## Webhook Integration

### Distribution Completed Webhook

When cost distribution is completed (for async operations), a webhook is sent:

```http
POST {webhook_url}
Content-Type: application/json
X-Webhook-Signature: sha256={signature}

{
    "event": "cost_distribution.completed",
    "data": {
        "distribution_id": "uuid-here",
        "service_configuration_id": 123,
        "total_cost": 1500.00,
        "property_count": 4,
        "completed_at": "2024-03-15T10:30:00Z",
        "results": {
            "distributed_amounts": {
                "1": 375.00,
                "2": 375.00,
                "3": 375.00,
                "4": 375.00
            },
            "total_distributed": 1500.00
        }
    }
}
```

### Webhook Verification

Verify webhook signatures using HMAC-SHA256:

```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
$expectedSignature = 'sha256=' . $signature;

if (!hash_equals($expectedSignature, $receivedSignature)) {
    throw new InvalidSignatureException('Invalid webhook signature');
}
```

## Testing

### API Testing Examples

```php
// Feature test example
public function test_can_distribute_costs_via_api(): void
{
    $user = User::factory()->create();
    $serviceConfig = ServiceConfiguration::factory()->create([
        'tenant_id' => $user->tenant_id,
        'distribution_method' => DistributionMethod::EQUAL,
    ]);
    $properties = Property::factory()->count(4)->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/cost-distribution/distribute', [
            'service_configuration_id' => $serviceConfig->id,
            'property_ids' => $properties->pluck('id')->toArray(),
            'total_cost' => 1000.00,
            'billing_period' => [
                'start_date' => '2024-03-01',
                'end_date' => '2024-03-31',
            ],
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'distributed_amounts',
                'total_distributed',
                'statistics',
            ],
        ]);

    $data = $response->json('data');
    $this->assertEquals(1000.00, $data['total_distributed']);
    $this->assertCount(4, $data['distributed_amounts']);
}
```

## Performance Considerations

### Optimization Guidelines

1. **Batch Processing**: For large property sets, consider async processing
2. **Caching**: Cache service configurations and property data
3. **Pagination**: Limit property count per request (max 1000)
4. **Validation**: Pre-validate inputs to avoid expensive calculations

### Monitoring

Monitor API performance metrics:

- Request duration
- Distribution calculation time
- Error rates by endpoint
- Rate limit hit rates

## Related Documentation

- [Shared Service Cost Distribution Service](../services/SHARED_SERVICE_COST_DISTRIBUTION.md)
- [Property-Based Testing](../testing/PROPERTY_BASED_TESTING_SHARED_SERVICES.md)
- [API Authentication Guide](API_AUTHENTICATION.md)
- [Webhook Integration Guide](WEBHOOK_INTEGRATION.md)