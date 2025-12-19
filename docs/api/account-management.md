# Account Management API

## Overview

The Account Management API provides endpoints for managing hierarchical user accounts in the multi-tenant utilities billing platform. All endpoints require authentication and enforce role-based access control.

**Base URL**: `/api/v1/accounts`

**Authentication**: Bearer token (Laravel Sanctum)

**Content-Type**: `application/json`

---

## Endpoints

### Create Admin Account

Creates a new admin account with optional subscription.

**Endpoint**: `POST /api/v1/accounts/admins`

**Authorization**: Superadmin only

**Request Body**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "organization_name": "Acme Properties",
  "plan_type": "professional",
  "expires_at": "2025-12-31"
}
```

**Request Fields**:
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Yes | max:255 | Admin's full name |
| `email` | string | Yes | email, unique | Unique email address |
| `password` | string | Yes | min:8 | Plain text password |
| `organization_name` | string | Yes | max:255 | Organization name |
| `plan_type` | string | No | enum: basic, professional, enterprise | Subscription plan |
| `expires_at` | string | No | date, after:today | Subscription expiry (ISO 8601) |

**Success Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin",
    "tenant_id": 347821,
    "organization_name": "Acme Properties",
    "is_active": true,
    "subscription": {
      "id": 1,
      "plan_type": "professional",
      "status": "active",
      "starts_at": "2025-11-25T10:00:00Z",
      "expires_at": "2025-12-31T23:59:59Z",
      "max_properties": 100,
      "max_tenants": 500
    },
    "created_at": "2025-11-25T10:00:00Z",
    "updated_at": "2025-11-25T10:00:00Z"
  },
  "message": "Admin account created successfully"
}
```

**Error Responses**:

*401 Unauthorized*:
```json
{
  "message": "Unauthenticated"
}
```

*403 Forbidden*:
```json
{
  "message": "This action is unauthorized"
}
```

*422 Unprocessable Entity*:
```json
{
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

### Create Tenant Account

Creates a tenant account and assigns them to a property.

**Endpoint**: `POST /api/v1/accounts/tenants`

**Authorization**: Admin or Manager

**Request Body**:
```json
{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "password": "TenantPass123",
  "property_id": 42
}
```

**Request Fields**:
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Yes | max:255 | Tenant's full name |
| `email` | string | Yes | email, unique | Unique email address |
| `password` | string | Yes | min:8 | Plain text password |
| `property_id` | integer | Yes | exists:properties | Property to assign tenant to |

**Success Response** (201 Created):
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "tenant",
    "tenant_id": 347821,
    "property_id": 42,
    "parent_user_id": 1,
    "is_active": true,
    "property": {
      "id": 42,
      "name": "Apartment 101",
      "address": "123 Main St"
    },
    "parent_user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "created_at": "2025-11-25T10:30:00Z",
    "updated_at": "2025-11-25T10:30:00Z"
  },
  "message": "Tenant account created successfully. Welcome email sent."
}
```

**Error Responses**:

*403 Forbidden*:
```json
{
  "message": "Cannot assign tenant to property from different organization"
}
```

*422 Unprocessable Entity*:
```json
{
  "message": "The given data was invalid",
  "errors": {
    "property_id": ["The selected property id is invalid."]
  }
}
```

---

### Reassign Tenant

Reassigns a tenant from one property to another.

**Endpoint**: `PUT /api/v1/accounts/tenants/{tenant}/reassign`

**Authorization**: Admin or Manager (must own both tenant and property)

**Request Body**:
```json
{
  "property_id": 43
}
```

**Request Fields**:
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `property_id` | integer | Yes | exists:properties | New property to assign tenant to |

**Success Response** (200 OK):
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "property_id": 43,
    "previous_property_id": 42,
    "updated_at": "2025-11-25T11:00:00Z"
  },
  "message": "Tenant reassigned successfully. Notification email sent."
}
```

**Error Responses**:

*403 Forbidden*:
```json
{
  "message": "Cannot reassign tenant to property from different organization"
}
```

*404 Not Found*:
```json
{
  "message": "Tenant not found"
}
```

---

### Deactivate Account

Deactivates a user account (soft disable).

**Endpoint**: `POST /api/v1/accounts/{user}/deactivate`

**Authorization**: Admin or Manager (for their tenants), Superadmin (for any user)

**Request Body**:
```json
{
  "reason": "Lease ended - moved out"
}
```

**Request Fields**:
| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `reason` | string | No | max:500 | Reason for deactivation |

**Success Response** (200 OK):
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "is_active": false,
    "deactivated_at": "2025-11-25T12:00:00Z",
    "deactivation_reason": "Lease ended - moved out"
  },
  "message": "Account deactivated successfully"
}
```

---

### Reactivate Account

Reactivates a previously deactivated account.

**Endpoint**: `POST /api/v1/accounts/{user}/reactivate`

**Authorization**: Admin or Manager (for their tenants), Superadmin (for any user)

**Request Body**: None

**Success Response** (200 OK):
```json
{
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "is_active": true,
    "reactivated_at": "2025-11-25T13:00:00Z"
  },
  "message": "Account reactivated successfully"
}
```

---

### Delete Account

Permanently deletes a user account (with dependency validation).

**Endpoint**: `DELETE /api/v1/accounts/{user}`

**Authorization**: Admin or Manager (for their tenants), Superadmin (for any user)

**Request Body**: None

**Success Response** (200 OK):
```json
{
  "message": "Account deleted successfully"
}
```

**Error Responses**:

*409 Conflict*:
```json
{
  "message": "Cannot delete user because it has associated meter readings and child users. Please deactivate instead.",
  "dependencies": {
    "meter_readings": true,
    "child_users": true
  },
  "suggestion": "Use deactivation instead of deletion to preserve historical data"
}
```

---

## Authentication

All endpoints require authentication using Laravel Sanctum bearer tokens.

**Request Header**:
```
Authorization: Bearer {token}
```

**Example**:
```bash
curl -X POST https://api.example.com/api/v1/accounts/tenants \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "password": "TenantPass123",
    "property_id": 42
  }'
```

---

## Authorization

### Role-Based Access Control

| Endpoint | Superadmin | Admin | Manager | Tenant |
|----------|------------|-------|---------|--------|
| Create Admin | ✅ | ❌ | ❌ | ❌ |
| Create Tenant | ✅ | ✅ | ✅ | ❌ |
| Reassign Tenant | ✅ | ✅ | ✅ | ❌ |
| Deactivate Account | ✅ | ✅* | ✅* | ❌ |
| Reactivate Account | ✅ | ✅* | ✅* | ❌ |
| Delete Account | ✅ | ✅* | ✅* | ❌ |

*Admin/Manager can only manage users within their organization (same tenant_id)

### Multi-Tenancy Enforcement

All operations enforce tenant isolation:
- Admins can only manage users with their `tenant_id`
- Property ownership validated before assignments
- Cross-tenant operations explicitly prevented

---

## Rate Limiting

API endpoints are rate-limited to prevent abuse:

- **Authenticated requests**: 60 requests per minute
- **Account creation**: 10 requests per minute
- **Bulk operations**: 5 requests per minute

**Rate Limit Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1732536000
```

**Rate Limit Exceeded** (429 Too Many Requests):
```json
{
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

---

## Error Handling

### Standard Error Response Format

```json
{
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., dependencies) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

---

## Webhooks

Account management events can trigger webhooks for external integrations.

### Available Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `account.created` | Admin or tenant account created | User data |
| `account.deactivated` | Account deactivated | User data + reason |
| `account.reactivated` | Account reactivated | User data |
| `account.deleted` | Account deleted | User ID only |
| `tenant.reassigned` | Tenant moved to new property | User data + property IDs |

### Webhook Configuration

Configure webhooks in your admin panel or via API:

```json
{
  "url": "https://your-app.com/webhooks/accounts",
  "events": ["account.created", "tenant.reassigned"],
  "secret": "your-webhook-secret"
}
```

### Webhook Payload Example

```json
{
  "event": "account.created",
  "timestamp": "2025-11-25T10:00:00Z",
  "data": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com",
    "role": "tenant",
    "tenant_id": 347821,
    "property_id": 42
  }
}
```

---

## SDK Examples

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->post('https://api.example.com/api/v1/accounts/tenants', [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => 'TenantPass123',
        'property_id' => 42,
    ]);

if ($response->successful()) {
    $tenant = $response->json('data');
    echo "Tenant created: {$tenant['id']}";
}
```

### JavaScript (Axios)

```javascript
const axios = require('axios');

const response = await axios.post(
  'https://api.example.com/api/v1/accounts/tenants',
  {
    name: 'Jane Smith',
    email: 'jane@example.com',
    password: 'TenantPass123',
    property_id: 42
  },
  {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  }
);

console.log('Tenant created:', response.data.data.id);
```

### Python (Requests)

```python
import requests

response = requests.post(
    'https://api.example.com/api/v1/accounts/tenants',
    json={
        'name': 'Jane Smith',
        'email': 'jane@example.com',
        'password': 'TenantPass123',
        'property_id': 42
    },
    headers={
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
)

if response.status_code == 201:
    tenant = response.json()['data']
    print(f"Tenant created: {tenant['id']}")
```

---

## Testing

### Postman Collection

Import the Postman collection for easy API testing:

**Collection URL**: `https://api.example.com/postman/account-management.json`

### Test Credentials

**Superadmin**:
- Email: `superadmin@example.com`
- Password: `password`

**Admin**:
- Email: `admin@example.com`
- Password: `password`

**Note**: Test credentials only available in development/staging environments.

---

## Related Documentation

- [AccountManagementService Documentation](../services/AccountManagementService.md)
- [Authentication Guide](authentication.md)
- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Webhook Configuration](./webhooks.md)

---

## Support

For API support:
- Email: api-support@example.com
- Documentation: https://docs.example.com
- Status Page: https://status.example.com
