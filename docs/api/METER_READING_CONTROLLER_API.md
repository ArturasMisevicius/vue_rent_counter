# Meter Reading Controller API Reference

Quick reference for meter reading management endpoints.

---

## Web Endpoints

### Update Meter Reading (Correction)

**Endpoint**: `PUT /manager/meter-readings/{meterReading}/correct`  
**Auth**: Manager, Admin  
**Controller**: `MeterReadingUpdateController`

**Request**:
```json
{
  "value": 1050.00,
  "change_reason": "Corrected misread digit from 1000 to 1050",
  "reading_date": "2024-01-15",  // Optional
  "zone": "day"                   // Optional
}
```

**Validation**:
- `value`: Required, numeric, >= 0, must maintain monotonicity
- `change_reason`: Required, string, min 10 chars, max 500 chars
- `reading_date`: Optional, date, <= today
- `zone`: Optional, string, max 50 chars

**Response**: Redirect with success message

---

## API Endpoints

### Create Meter Reading

**Endpoint**: `POST /api/meter-readings`  
**Auth**: Manager, Admin  
**Controller**: `MeterReadingApiController::store`

**Request**:
```json
{
  "meter_id": 1,
  "reading_date": "2024-01-15",
  "value": 1234.56,
  "zone": "day"  // Required for multi-zone meters
}
```

**Response** (201):
```json
{
  "id": 123,
  "meter_id": 1,
  "reading_date": "2024-01-15",
  "value": "1234.56",
  "zone": "day",
  "entered_by": 5,
  "created_at": "2024-01-15T10:30:00Z"
}
```

---

### Get Meter Reading

**Endpoint**: `GET /api/meter-readings/{meterReading}`  
**Auth**: Manager, Admin  
**Controller**: `MeterReadingApiController::show`

**Response** (200):
```json
{
  "id": 123,
  "meter_id": 1,
  "reading_date": "2024-01-15",
  "value": "1234.56",
  "zone": "day",
  "entered_by": 5,
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z",
  "consumption": "34.56",
  "meter": {
    "id": 1,
    "serial_number": "LT-2024-001",
    "type": "electricity",
    "supports_zones": true
  }
}
```

---

### Update Meter Reading

**Endpoint**: `PUT /api/meter-readings/{meterReading}`  
**Auth**: Manager, Admin  
**Controller**: `MeterReadingApiController::update`

**Request**:
```json
{
  "value": 1250.00,
  "change_reason": "Corrected misread digit from 1234 to 1250",
  "reading_date": "2024-01-15",  // Optional
  "zone": "day"                   // Optional
}
```

**Response** (200):
```json
{
  "id": 123,
  "meter_id": 1,
  "reading_date": "2024-01-15",
  "value": "1250.00",
  "zone": "day",
  "entered_by": 5,
  "updated_at": "2024-01-16T14:20:00Z",
  "audit": {
    "old_value": "1234.56",
    "new_value": "1250.00",
    "change_reason": "Corrected misread digit from 1234 to 1250",
    "changed_by": 5
  }
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "value": ["Reading cannot be lower than previous reading (1200.00)"],
    "change_reason": ["The change reason must be at least 10 characters."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\MeterReading] 123"
}
```

---

## Validation Rules

### Monotonicity
- New reading value must be >= previous reading value
- Updated reading value must be >= previous reading value
- Updated reading value must be <= next reading value (if exists)

### Temporal
- Reading date must be <= today
- Cannot create readings in the future

### Multi-Zone
- Zone is required for meters with `supports_zones = true`
- Zone is not allowed for meters with `supports_zones = false`
- Valid zones: "day", "night" (for electricity meters)

### Change Reason
- Required for all updates
- Minimum length: 10 characters
- Maximum length: 500 characters
- Must be descriptive and meaningful

---

## Automatic Behaviors

### Audit Trail
- Every update automatically creates a `MeterReadingAudit` record
- Captures: old_value, new_value, change_reason, changed_by_user_id
- Immutable audit records

### Invoice Recalculation
- Draft invoices are automatically recalculated when readings change
- Finalized invoices are protected (immutable)
- Snapshot values are updated in invoice items

### Tenant Scoping
- All readings are automatically scoped to the authenticated user's tenant_id
- Cross-tenant access is prevented
- Tenant isolation is enforced at the database level

---

## Usage Examples

### cURL Examples

**Create Reading**:
```bash
curl -X POST https://api.example.com/api/meter-readings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "meter_id": 1,
    "reading_date": "2024-01-15",
    "value": 1234.56,
    "zone": "day"
  }'
```

**Update Reading**:
```bash
curl -X PUT https://api.example.com/api/meter-readings/123 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "value": 1250.00,
    "change_reason": "Corrected misread digit"
  }'
```

**Get Reading**:
```bash
curl -X GET https://api.example.com/api/meter-readings/123 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Related Documentation

- [Meter Reading Observer Implementation](../implementation/METER_READING_OBSERVER_IMPLEMENTATION.md)
- [Draft Invoice Recalculation](../implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md)
- [Meter Reading Controllers Implementation](../implementation/METER_READING_CONTROLLERS_IMPLEMENTATION.md)
