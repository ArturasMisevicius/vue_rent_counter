# Tariff API Documentation

## Overview

The Tariff API provides endpoints for managing utility tariffs with support for both provider-integrated and manually entered tariffs.

## Endpoints

### List Tariffs

**GET** `/api/tariffs`

Retrieve a paginated list of tariffs with optional filtering.

**Query Parameters:**
- `provider_id` (optional): Filter by provider ID
- `active` (optional): Filter by active status (true/false)
- `type` (optional): Filter by tariff type (flat, time_of_use)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "provider_id": 5,
      "remote_id": "EXT-12345",
      "name": "Standard Electricity Rate",
      "configuration": {
        "type": "flat",
        "rate": 0.15,
        "currency": "EUR"
      },
      "active_from": "2025-01-01",
      "active_until": null,
      "is_currently_active": true,
      "is_manual": false,
      "created_at": "2025-12-05T10:00:00Z",
      "updated_at": "2025-12-05T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

### Create Tariff

**POST** `/api/tariffs`

Create a new tariff with optional provider integration.

**Authorization:** SUPERADMIN or ADMIN role required

**Request Body:**
```json
{
  "provider_id": 5,
  "remote_id": "EXT-12345",
  "name": "Standard Electricity Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.15,
    "currency": "EUR"
  },
  "active_from": "2025-01-01",
  "active_until": null
}
```

**Manual Tariff Example:**
```json
{
  "provider_id": null,
  "name": "Manual Historical Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.12,
    "currency": "EUR"
  },
  "active_from": "2024-01-01",
  "active_until": "2024-12-31"
}
```

**Validation Rules:**
- `provider_id`: nullable, exists:providers,id
- `remote_id`: nullable, string, max:255, required_with:provider_id
- `name`: required, string, max:255, regex:/^[a-zA-Z0-9\s\-\_\.\,\(\)]+$/u
- `configuration`: required, array
- `configuration.type`: required, in:flat,time_of_use
- `configuration.currency`: required, in:EUR
- `configuration.rate`: required_if:configuration.type,flat, numeric, min:0, max:999999.9999
- `configuration.zones`: required_if:configuration.type,time_of_use, array, min:1
- `active_from`: required, date
- `active_until`: nullable, date, after:active_from

**Response (201 Created):**
```json
{
  "data": {
    "id": 51,
    "provider_id": 5,
    "remote_id": "EXT-12345",
    "name": "Standard Electricity Rate",
    "configuration": {
      "type": "flat",
      "rate": 0.15,
      "currency": "EUR"
    },
    "active_from": "2025-01-01",
    "active_until": null,
    "is_currently_active": true,
    "is_manual": false,
    "created_at": "2025-12-05T10:00:00Z",
    "updated_at": "2025-12-05T10:00:00Z"
  }
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "provider_id": [
      "Provider is required when external ID is provided"
    ],
    "remote_id": [
      "External ID may not be greater than 255 characters"
    ]
  }
}
```

### Update Tariff

**PUT/PATCH** `/api/tariffs/{id}`

Update an existing tariff.

**Authorization:** SUPERADMIN or ADMIN role required

**Request Body:** Same as Create Tariff

**Response (200 OK):** Same structure as Create Tariff

### Delete Tariff

**DELETE** `/api/tariffs/{id}`

Delete a tariff.

**Authorization:** SUPERADMIN or ADMIN role required

**Response (204 No Content)**

## Field Descriptions

### provider_id
- **Type:** Integer (nullable)
- **Description:** Foreign key to providers table
- **Null Value:** Indicates a manual tariff not linked to provider integration
- **Use Case:** Set to null for manually entered tariffs

### remote_id
- **Type:** String (nullable, max: 255)
- **Description:** External identifier from provider or billing system
- **Use Case:** Synchronization with external systems
- **Validation:** Required if provider_id is set (in some contexts)
- **Index:** Database indexed for fast lookups

### name
- **Type:** String (required, max: 255)
- **Description:** Human-readable tariff name
- **Validation:** Alphanumeric with spaces and basic punctuation
- **Security:** XSS protection via regex and sanitization

### configuration
- **Type:** JSON Object (required)
- **Description:** Tariff rate configuration
- **Structure:** Varies by type (flat vs time_of_use)

### active_from
- **Type:** Date (required)
- **Description:** Start date for tariff validity
- **Format:** YYYY-MM-DD

### active_until
- **Type:** Date (nullable)
- **Description:** End date for tariff validity
- **Format:** YYYY-MM-DD
- **Null Value:** Indicates no end date (indefinite)

## Computed Attributes

### is_currently_active
- **Type:** Boolean
- **Description:** Whether tariff is active on current date
- **Computed:** Based on active_from and active_until

### is_manual
- **Type:** Boolean
- **Description:** Whether tariff is manually entered (no provider)
- **Computed:** True when provider_id is null

## Configuration Types

### Flat Rate Configuration

```json
{
  "type": "flat",
  "rate": 0.15,
  "currency": "EUR",
  "fixed_fee": 5.00
}
```

### Time-of-Use Configuration

```json
{
  "type": "time_of_use",
  "currency": "EUR",
  "zones": [
    {
      "id": "day",
      "start": "07:00",
      "end": "23:00",
      "rate": 0.18
    },
    {
      "id": "night",
      "start": "23:00",
      "end": "07:00",
      "rate": 0.12
    }
  ],
  "weekend_logic": "apply_night_rate",
  "fixed_fee": 5.00
}
```

## Error Codes

- **400 Bad Request:** Invalid request format
- **401 Unauthorized:** Missing or invalid authentication
- **403 Forbidden:** Insufficient permissions (requires SUPERADMIN/ADMIN)
- **404 Not Found:** Tariff not found
- **422 Unprocessable Entity:** Validation errors
- **500 Internal Server Error:** Server error

## Rate Limiting

- **Authenticated:** 60 requests per minute
- **Unauthenticated:** 10 requests per minute

## Examples

### Create Manual Tariff (cURL)

```bash
curl -X POST https://api.example.com/api/tariffs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider_id": null,
    "name": "Manual Historical Rate 2024",
    "configuration": {
      "type": "flat",
      "rate": 0.12,
      "currency": "EUR"
    },
    "active_from": "2024-01-01",
    "active_until": "2024-12-31"
  }'
```

### Create Provider-Linked Tariff (cURL)

```bash
curl -X POST https://api.example.com/api/tariffs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider_id": 5,
    "remote_id": "EXT-12345",
    "name": "Provider Standard Rate",
    "configuration": {
      "type": "flat",
      "rate": 0.15,
      "currency": "EUR"
    },
    "active_from": "2025-01-01"
  }'
```

## Related Documentation

- [Tariff Manual Mode](../filament/TARIFF_MANUAL_MODE.md)
- [Provider Integration](../filament/PROVIDER_INTEGRATION.md)
- [Authentication](authentication.md)
- [Authorization](./AUTHORIZATION.md)
