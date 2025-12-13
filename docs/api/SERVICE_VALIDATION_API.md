# Service Validation API Documentation

## Overview

The Service Validation API provides endpoints for validating utility service configurations and meter readings. It integrates with the `ServiceValidationEngine` to provide comprehensive validation capabilities.

## Authentication

All API endpoints require authentication and appropriate permissions:

- **Meter Reading Validation**: Requires `view` permission on the meter reading
- **Rate Change Validation**: Requires `update` permission on the service configuration
- **Batch Operations**: Requires appropriate permissions on all included resources

## Base URL

```
/api/v1/validation
```

## Endpoints

### Validate Meter Reading

Validates a single meter reading against all applicable validation rules.

```http
POST /api/v1/validation/meter-reading/{reading}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `reading` | integer | Yes | Meter reading ID |
| `service_configuration_id` | integer | No | Override service configuration |
| `validation_options` | object | No | Additional validation options |

#### Request Body

```json
{
    "service_configuration_id": 123,
    "validation_options": {
        "skip_seasonal_validation": false,
        "strict_mode": true,
        "include_recommendations": true
    }
}
```

#### Response

```json
{
    "success": true,
    "data": {
        "is_valid": true,
        "errors": [],
        "warnings": [
            "Consumption is 15% higher than historical average"
        ],
        "recommendations": [
            "Consider checking for leaks if high consumption continues"
        ],
        "validation_metadata": {
            "validated_at": "2024-12-13T10:30:00Z",
            "validators_applied": ["consumption", "seasonal", "data_quality"],
            "validation_duration_ms": 45,
            "cache_hits": 3
        }
    }
}
```

#### Error Responses

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_FAILED",
        "message": "Meter reading validation failed",
        "details": {
            "is_valid": false,
            "errors": [
                "Consumption exceeds maximum allowed limit",
                "Reading date is in the future"
            ],
            "warnings": [],
            "recommendations": []
        }
    }
}
```

### Validate Rate Change

Validates proposed rate changes against business rules and restrictions.

```http
POST /api/v1/validation/rate-change/{serviceConfiguration}
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `serviceConfiguration` | integer | Yes | Service configuration ID |

#### Request Body

```json
{
    "new_rate_schedule": {
        "rate_per_unit": 0.15,
        "monthly_rate": 25.00,
        "effective_from": "2024-01-01",
        "time_slots": [
            {
                "day_type": "weekday",
                "start_hour": 8,
                "end_hour": 18,
                "rate": 0.18,
                "zone": "peak"
            }
        ]
    },
    "validation_options": {
        "check_advance_notice": true,
        "allow_retroactive": false
    }
}
```

#### Response

```json
{
    "success": true,
    "data": {
        "is_valid": false,
        "errors": [
            "Rate change requires 7 days advance notice"
        ],
        "warnings": [
            "New rate is 20% higher than current rate"
        ],
        "recommendations": [
            "Consider phased implementation to reduce impact"
        ],
        "validation_metadata": {
            "validated_at": "2024-12-13T10:30:00Z",
            "current_rate_schedule": {
                "rate_per_unit": 0.12,
                "last_changed": "2024-11-01"
            },
            "change_frequency_check": {
                "days_since_last_change": 15,
                "minimum_required": 30
            }
        }
    }
}
```

### Batch Validate Readings

Validates multiple meter readings in a single request with optimized performance.

```http
POST /api/v1/validation/batch/meter-readings
```

#### Request Body

```json
{
    "reading_ids": [1, 2, 3, 4, 5],
    "validation_options": {
        "parallel_processing": true,
        "include_performance_metrics": true,
        "stop_on_first_error": false
    }
}
```

#### Response

```json
{
    "success": true,
    "data": {
        "total_readings": 5,
        "valid_readings": 4,
        "invalid_readings": 1,
        "warnings_count": 2,
        "results": {
            "1": {
                "is_valid": true,
                "errors": [],
                "warnings": []
            },
            "2": {
                "is_valid": false,
                "errors": ["Consumption exceeds limit"],
                "warnings": []
            }
        },
        "summary": {
            "validation_rate": 80.0,
            "average_warnings_per_reading": 0.4,
            "error_rate": 20.0
        },
        "performance_metrics": {
            "duration": 0.234,
            "cache_hits": 12,
            "database_queries": 3,
            "memory_peak_mb": 45.2
        }
    }
}
```

### Get Validation Rules

Retrieves validation rules for a specific utility service or service configuration.

```http
GET /api/v1/validation/rules/{serviceConfiguration}
```

#### Response

```json
{
    "success": true,
    "data": {
        "service_configuration_id": 123,
        "utility_service": {
            "id": 456,
            "name": "Electricity",
            "unit_of_measurement": "kWh"
        },
        "validation_rules": {
            "consumption_limits": {
                "min": 0,
                "max": 1000,
                "variance_threshold": 0.3
            },
            "seasonal_adjustments": {
                "summer_threshold": 200,
                "winter_threshold": 400,
                "variance_threshold": 0.2
            },
            "business_constraints": [
                {
                    "field": "consumption",
                    "operator": "<=",
                    "value": 1000,
                    "message": "Consumption cannot exceed 1000 kWh",
                    "severity": "error"
                }
            ]
        },
        "effective_from": "2024-01-01T00:00:00Z",
        "last_updated": "2024-12-01T10:00:00Z"
    }
}
```

### Validation Health Check

Checks the health and performance of the validation system.

```http
GET /api/v1/validation/health
```

#### Response

```json
{
    "success": true,
    "data": {
        "status": "healthy",
        "validators": {
            "consumption": "active",
            "seasonal": "active",
            "data_quality": "active",
            "business_rules": "active",
            "input_method": "active",
            "rate_change": "active"
        },
        "cache_status": {
            "validation_rules": "healthy",
            "historical_data": "healthy",
            "hit_rate": 0.85
        },
        "performance_metrics": {
            "average_validation_time_ms": 42,
            "success_rate": 0.98,
            "error_rate": 0.02
        },
        "system_info": {
            "memory_usage_mb": 128.5,
            "active_validations": 3,
            "queue_size": 0
        }
    }
}
```

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_FAILED` | 422 | Validation rules failed |
| `UNAUTHORIZED` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `INVALID_INPUT` | 400 | Invalid request parameters |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `SYSTEM_ERROR` | 500 | Internal validation system error |
| `BATCH_SIZE_EXCEEDED` | 413 | Batch size too large |
| `TIMEOUT` | 408 | Validation timeout |

## Rate Limiting

API endpoints are rate-limited to ensure system stability:

- **Individual Validation**: 100 requests per minute per user
- **Batch Validation**: 10 requests per minute per user
- **Health Check**: 60 requests per minute per user

Rate limit headers are included in responses:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1702468200
```

## Webhooks

The validation system can send webhooks for important events:

### Validation Failure Webhook

Triggered when validation fails for critical readings.

```json
{
    "event": "validation.failed",
    "timestamp": "2024-12-13T10:30:00Z",
    "data": {
        "reading_id": 123,
        "meter_id": 456,
        "property_id": 789,
        "errors": [
            "Consumption exceeds maximum allowed limit"
        ],
        "severity": "high"
    }
}
```

### Anomaly Detection Webhook

Triggered when anomalies are detected in consumption patterns.

```json
{
    "event": "validation.anomaly_detected",
    "timestamp": "2024-12-13T10:30:00Z",
    "data": {
        "reading_id": 123,
        "anomaly_type": "consumption_spike",
        "deviation": 2.5,
        "threshold": 2.0,
        "recommendations": [
            "Check for meter malfunction",
            "Verify reading accuracy"
        ]
    }
}
```

## SDK Examples

### PHP SDK

```php
use App\Services\ValidationApiClient;

$client = new ValidationApiClient();

// Validate single reading
$result = $client->validateReading(123, [
    'service_configuration_id' => 456,
    'validation_options' => [
        'strict_mode' => true
    ]
]);

if (!$result['is_valid']) {
    foreach ($result['errors'] as $error) {
        echo "Validation error: {$error}\n";
    }
}

// Batch validation
$batchResult = $client->batchValidateReadings([1, 2, 3, 4, 5]);
echo "Validation rate: {$batchResult['summary']['validation_rate']}%\n";
```

### JavaScript SDK

```javascript
import { ValidationClient } from '@/services/validation-client';

const client = new ValidationClient();

// Validate reading
const result = await client.validateReading(123, {
    serviceConfigurationId: 456,
    validationOptions: {
        strictMode: true
    }
});

if (!result.isValid) {
    result.errors.forEach(error => {
        console.error('Validation error:', error);
    });
}

// Batch validation
const batchResult = await client.batchValidateReadings([1, 2, 3, 4, 5]);
console.log(`Validation rate: ${batchResult.summary.validationRate}%`);
```

## Testing

### Test Endpoints

Test endpoints are available in development and staging environments:

```http
POST /api/v1/validation/test/meter-reading
POST /api/v1/validation/test/rate-change
POST /api/v1/validation/test/batch
```

These endpoints use mock data and don't affect production systems.

### Example Test Requests

```bash
# Test meter reading validation
curl -X POST \
  http://localhost:8000/api/v1/validation/test/meter-reading \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{
    "consumption": 150,
    "reading_date": "2024-12-13",
    "utility_type": "electricity"
  }'

# Test rate change validation
curl -X POST \
  http://localhost:8000/api/v1/validation/test/rate-change \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -d '{
    "current_rate": 0.12,
    "new_rate": 0.15,
    "effective_date": "2024-01-01"
  }'
```

## Monitoring and Analytics

### Metrics Available

- Validation success/failure rates
- Average validation times
- Cache hit rates
- Error distribution by type
- Performance trends over time

### Monitoring Endpoints

```http
GET /api/v1/validation/metrics
GET /api/v1/validation/analytics/daily
GET /api/v1/validation/analytics/weekly
```

### Example Metrics Response

```json
{
    "success": true,
    "data": {
        "period": "last_24_hours",
        "total_validations": 1250,
        "success_rate": 0.982,
        "average_response_time_ms": 45,
        "cache_hit_rate": 0.87,
        "error_breakdown": {
            "consumption_limit_exceeded": 12,
            "invalid_date": 5,
            "unauthorized": 3
        },
        "performance_trends": {
            "response_time_trend": "stable",
            "error_rate_trend": "decreasing",
            "cache_efficiency_trend": "improving"
        }
    }
}
```