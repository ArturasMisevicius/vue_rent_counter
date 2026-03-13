# Meter Reading API Documentation

## Overview

The Meter Reading API provides endpoints for managing meter readings with Truth-but-Verify workflow support. All endpoints require authentication and respect tenant boundaries.

## Base URL

```
/api/meter-readings
```

## Authentication

All endpoints require authentication via Laravel Sanctum:

```http
Authorization: Bearer {token}
```

## Endpoints

### List Meter Readings

```http
GET /api/meter-readings
```

**Description**: Retrieve paginated list of meter readings accessible to the authenticated user.

**Authorization**: All authenticated users (scoped by role)

**Query Parameters**:
- `page` (integer, optional): Page number for pagination
- `per_page` (integer, optional): Items per page (max 100)
- `meter_id` (integer, optional): Filter by specific meter
- `validation_status` (string, optional): Filter by validation status
- `reading_date_from` (date, optional): Filter readings from date
- `reading_date_to` (date, optional): Filter readings to date

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "meter_id": 123,
      "value": 1000.50,
      "reading_date": "2024-01-15",
      "zone": "day",
      "validation_status": "pending",
      "input_method": "manual",
      "entered_by": 456,
      "validated_by": null,
      "validated_at": null,
      "validation_notes": null,
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z",
      "meter": {
        "id": 123,
        "serial_number": "MTR-001",
        "type": "electricity"
      },
      "entered_by_user": {
        "id": 456,
        "name": "John Tenant",
        "role": "tenant"
      }
    }
  ],
  "links": {
    "first": "/api/meter-readings?page=1",
    "last": "/api/meter-readings?page=5",
    "prev": null,
    "next": "/api/meter-readings?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

### Get Meter Reading

```http
GET /api/meter-readings/{id}
```

**Description**: Retrieve a specific meter reading.

**Authorization**: 
- Superadmin: All readings
- Admin: All readings
- Manager: Same tenant only
- Tenant: Own property only

**Response**:
```json
{
  "data": {
    "id": 1,
    "meter_id": 123,
    "value": 1000.50,
    "reading_date": "2024-01-15",
    "zone": "day",
    "validation_status": "pending",
    "input_method": "manual",
    "entered_by": 456,
    "validated_by": null,
    "validated_at": null,
    "validation_notes": null,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "meter": {
      "id": 123,
      "serial_number": "MTR-001",
      "type": "electricity",
      "property": {
        "id": 789,
        "name": "Apartment 101",
        "address": "123 Main St"
      }
    },
    "entered_by_user": {
      "id": 456,
      "name": "John Tenant",
      "role": "tenant"
    },
    "validated_by_user": null
  }
}
```

### Create Meter Reading

```http
POST /api/meter-readings
```

**Description**: Create a new meter reading. Tenant submissions automatically require validation.

**Authorization**: All authenticated users

**Request Body**:
```json
{
  "meter_id": 123,
  "value": 1000.50,
  "reading_date": "2024-01-15",
  "zone": "day",
  "input_method": "manual",
  "photo_path": "/uploads/meter-photos/photo.jpg"
}
```

**Validation Rules**:
- `meter_id`: required, integer, exists in meters table, accessible to user
- `value`: required, numeric, min:0, monotonic validation
- `reading_date`: required, date, not future, not before last reading
- `zone`: nullable, string, max:50, required for multi-zone meters
- `input_method`: required, enum (manual, photo_ocr, csv_import, api_integration, estimated)
- `photo_path`: nullable, string, valid file path

**Response** (201 Created):
```json
{
  "data": {
    "id": 1,
    "meter_id": 123,
    "value": 1000.50,
    "reading_date": "2024-01-15",
    "zone": "day",
    "validation_status": "pending",
    "input_method": "manual",
    "entered_by": 456,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "message": "Meter reading created successfully. Awaiting manager approval."
}
```

### Update Meter Reading

```http
PUT /api/meter-readings/{id}
PATCH /api/meter-readings/{id}
```

**Description**: Update an existing meter reading.

**Authorization**: 
- Superadmin: All readings
- Admin: All readings  
- Manager: Same tenant only
- Tenant: Cannot update (Truth-but-Verify workflow)

**Request Body**: Same as create endpoint

**Response**:
```json
{
  "data": {
    "id": 1,
    "meter_id": 123,
    "value": 1050.75,
    "reading_date": "2024-01-15",
    "zone": "day",
    "validation_status": "pending",
    "input_method": "manual",
    "entered_by": 456,
    "updated_at": "2024-01-15T11:00:00Z"
  },
  "message": "Meter reading updated successfully."
}
```

### Approve Meter Reading

```http
PATCH /api/meter-readings/{id}/approve
```

**Description**: Approve a pending meter reading (Truth-but-Verify workflow).

**Authorization**: Manager+ roles only, same tenant scope

**Request Body**: None required

**Response**:
```json
{
  "data": {
    "id": 1,
    "validation_status": "validated",
    "validated_by": 789,
    "validated_at": "2024-01-15T12:00:00Z"
  },
  "message": "Meter reading approved successfully."
}
```

### Reject Meter Reading

```http
PATCH /api/meter-readings/{id}/reject
```

**Description**: Reject a pending meter reading with reason (Truth-but-Verify workflow).

**Authorization**: Manager+ roles only, same tenant scope

**Request Body**:
```json
{
  "validation_notes": "Reading value seems too high compared to previous readings."
}
```

**Validation Rules**:
- `validation_notes`: required, string, max:1000

**Response**:
```json
{
  "data": {
    "id": 1,
    "validation_status": "rejected",
    "validated_by": 789,
    "validated_at": "2024-01-15T12:00:00Z",
    "validation_notes": "Reading value seems too high compared to previous readings."
  },
  "message": "Meter reading rejected."
}
```

### Delete Meter Reading

```http
DELETE /api/meter-readings/{id}
```

**Description**: Delete a meter reading.

**Authorization**: Admin+ roles only

**Response**:
```json
{
  "message": "Meter reading deleted successfully."
}
```

### Bulk Operations

#### Bulk Approve

```http
PATCH /api/meter-readings/bulk/approve
```

**Description**: Approve multiple pending readings.

**Authorization**: Manager+ roles only

**Request Body**:
```json
{
  "reading_ids": [1, 2, 3, 4, 5]
}
```

**Response**:
```json
{
  "data": {
    "approved": 4,
    "failed": 1,
    "errors": [
      {
        "reading_id": 3,
        "error": "Reading is not in pending status"
      }
    ]
  },
  "message": "Bulk approval completed. 4 readings approved, 1 failed."
}
```

#### Bulk Export

```http
GET /api/meter-readings/export
```

**Description**: Export meter readings to CSV/Excel.

**Authorization**: All authenticated users (scoped data)

**Query Parameters**:
- `format` (string, optional): Export format (csv, xlsx) - default: csv
- `meter_id` (integer, optional): Filter by meter
- `date_from` (date, optional): Export from date
- `date_to` (date, optional): Export to date
- `validation_status` (string, optional): Filter by status

**Response**: File download with appropriate headers

## Error Responses

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "value": [
      "The value must be greater than the previous reading (950.25)."
    ],
    "reading_date": [
      "The reading date cannot be in the future."
    ]
  }
}
```

### Authorization Errors (403)

```json
{
  "message": "This action is unauthorized.",
  "error": "Cannot access meter reading from different tenant."
}
```

### Not Found Errors (404)

```json
{
  "message": "Meter reading not found."
}
```

### Business Logic Errors (400)

```json
{
  "message": "Cannot approve reading.",
  "error": "Reading is not in pending status.",
  "current_status": "validated"
}
```

## Rate Limiting

API endpoints are rate limited:
- **Authenticated users**: 60 requests per minute
- **Bulk operations**: 10 requests per minute
- **Export operations**: 5 requests per minute

Rate limit headers are included in responses:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1642694400
```

## Webhook Events

The API can trigger webhook events for workflow actions:

### meter_reading.created
```json
{
  "event": "meter_reading.created",
  "data": {
    "reading_id": 1,
    "meter_id": 123,
    "value": 1000.50,
    "validation_status": "pending",
    "entered_by": 456,
    "requires_approval": true
  },
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### meter_reading.approved
```json
{
  "event": "meter_reading.approved",
  "data": {
    "reading_id": 1,
    "validated_by": 789,
    "validated_at": "2024-01-15T12:00:00Z",
    "available_for_billing": true
  },
  "timestamp": "2024-01-15T12:00:00Z"
}
```

### meter_reading.rejected
```json
{
  "event": "meter_reading.rejected",
  "data": {
    "reading_id": 1,
    "validated_by": 789,
    "validated_at": "2024-01-15T12:00:00Z",
    "validation_notes": "Reading value seems incorrect",
    "requires_resubmission": true
  },
  "timestamp": "2024-01-15T12:00:00Z"
}
```

## SDK Examples

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

// Create meter reading
$response = Http::withToken($token)
    ->post('/api/meter-readings', [
        'meter_id' => 123,
        'value' => 1000.50,
        'reading_date' => '2024-01-15',
        'input_method' => 'manual',
    ]);

if ($response->successful()) {
    $reading = $response->json('data');
    echo "Reading created with ID: {$reading['id']}";
}

// Approve reading
$response = Http::withToken($token)
    ->patch("/api/meter-readings/{$readingId}/approve");

if ($response->successful()) {
    echo "Reading approved successfully";
}
```

### JavaScript

```javascript
// Create meter reading
const response = await fetch('/api/meter-readings', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    meter_id: 123,
    value: 1000.50,
    reading_date: '2024-01-15',
    input_method: 'manual'
  })
});

const data = await response.json();
if (response.ok) {
  console.log('Reading created:', data.data);
} else {
  console.error('Error:', data.errors);
}

// Approve reading
const approveResponse = await fetch(`/api/meter-readings/${readingId}/approve`, {
  method: 'PATCH',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

if (approveResponse.ok) {
  console.log('Reading approved');
}
```

## Testing

### Feature Test Examples

```php
/** @test */
public function tenant_can_create_meter_reading(): void
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $meter = Meter::factory()->create();
    
    $response = $this->actingAs($tenant)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'value' => 1000.50,
            'reading_date' => now()->format('Y-m-d'),
            'input_method' => 'manual',
        ]);
    
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'validation_status', 'entered_by'],
            'message'
        ]);
    
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.50,
        'validation_status' => 'pending',
        'entered_by' => $tenant->id,
    ]);
}

/** @test */
public function manager_can_approve_pending_reading(): void
{
    $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);
    
    $response = $this->actingAs($manager)
        ->patchJson("/api/meter-readings/{$reading->id}/approve");
    
    $response->assertOk()
        ->assertJson([
            'data' => [
                'validation_status' => 'validated',
                'validated_by' => $manager->id,
            ],
            'message' => 'Meter reading approved successfully.'
        ]);
}
```

## Related Documentation

- [MeterReadingPolicy](../policies/METER_READING_POLICY.md)
- [Truth-but-Verify Workflow](../workflows/TRUTH_BUT_VERIFY.md)
- [MeterReading Model](../models/METER_READING.md)
- [API Authentication](authentication.md)
- [Tenant Boundary Service](../services/TENANT_BOUNDARY_SERVICE.md)