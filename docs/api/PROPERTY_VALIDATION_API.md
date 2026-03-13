# Property Validation API Reference

**Version**: 1.0  
**Last Updated**: 2025-11-23  
**Status**: Production

---

## Overview

This document describes the validation rules, error responses, and translation keys used in property management operations across both Filament resources and API endpoints.

---

## Validation Rules

### Property Creation/Update

#### `address` (string, required)

**Rules**:
- `required`: Must be present
- `max:255`: Maximum 255 characters
- `string`: Must be a string value

**Translation Keys**:
- `properties.validation.address.required`
- `properties.validation.address.max`

**Example Valid Values**:
```
"Gedimino pr. 1-23, Vilnius"
"Savanorių g. 15, Kaunas"
"Taikos pr. 45-12"
```

**Example Invalid Values**:
```
"" (empty)
null
"<script>alert('xss')</script>" (contains HTML)
```

---

#### `type` (enum, required)

**Rules**:
- `required`: Must be present
- `enum:PropertyType`: Must be valid PropertyType enum value

**Valid Values**:
- `apartment`
- `house`

**Translation Keys**:
- `properties.validation.type.required`
- `properties.validation.type.enum`

**Example Request**:
```json
{
  "type": "apartment"
}
```

**Example Error Response**:
```json
{
  "message": "The property type must be either apartment or house.",
  "errors": {
    "type": [
      "The property type must be either apartment or house."
    ]
  }
}
```

---

#### `area_sqm` (numeric, required)

**Rules**:
- `required`: Must be present
- `numeric`: Must be a number
- `min:0`: Minimum value 0
- `max:10000`: Maximum value 10,000
- `decimal:0,2`: Up to 2 decimal places

**Translation Keys**:
- `properties.validation.area_sqm.required`
- `properties.validation.area_sqm.numeric`
- `properties.validation.area_sqm.min`
- `properties.validation.area_sqm.max`

**Example Valid Values**:
```json
{
  "area_sqm": 45.50
}
{
  "area_sqm": 120
}
{
  "area_sqm": 0.01
}
```

**Example Invalid Values**:
```json
{
  "area_sqm": -10
}  // Negative value

{
  "area_sqm": 15000
}  // Exceeds maximum

{
  "area_sqm": "not a number"
}  // Not numeric

{
  "area_sqm": 45.123
}  // Too many decimal places
```

---

#### `building_id` (integer, nullable)

**Rules**:
- `nullable`: Can be null
- `exists:buildings,id`: Must exist in buildings table
- Tenant scope: Must belong to authenticated user's tenant

**Translation Keys**:
- `properties.validation.building_id.exists`

**Example Valid Values**:
```json
{
  "building_id": 123
}
{
  "building_id": null
}
```

**Example Error Response**:
```json
{
  "message": "The selected building does not exist.",
  "errors": {
    "building_id": [
      "The selected building does not exist."
    ]
  }
}
```

---

#### `tenants` (array, nullable)

**Rules**:
- `nullable`: Can be null or empty array
- `array`: Must be an array
- `exists:users,id`: Each tenant ID must exist in users table
- Role check: Must have role `tenant`
- Tenant scope: Must belong to authenticated user's tenant

**Example Valid Values**:
```json
{
  "tenants": [456]
}
{
  "tenants": []
}
{
  "tenants": null
}
```

---

## Error Responses

### Validation Error (422)

**HTTP Status**: `422 Unprocessable Entity`

**Response Structure**:
```json
{
  "message": "The property address is required. (and 2 more errors)",
  "errors": {
    "address": [
      "The property address is required."
    ],
    "type": [
      "The property type is required."
    ],
    "area_sqm": [
      "The property area must be a number."
    ]
  }
}
```

### Authorization Error (403)

**HTTP Status**: `403 Forbidden`

**Response Structure**:
```json
{
  "message": "This action is unauthorized."
}
```

**Causes**:
- User lacks permission to create/update properties
- Attempting to access property outside tenant scope
- Policy check failed

### Not Found Error (404)

**HTTP Status**: `404 Not Found`

**Response Structure**:
```json
{
  "message": "Property not found."
}
```

---

## Translation Keys Reference

### Validation Messages

| Key | English Message | Usage |
|-----|----------------|-------|
| `properties.validation.address.required` | "The property address is required." | Address field empty |
| `properties.validation.address.max` | "The property address may not be greater than 255 characters." | Address too long |
| `properties.validation.type.required` | "The property type is required." | Type field empty |
| `properties.validation.type.enum` | "The property type must be either apartment or house." | Invalid type value |
| `properties.validation.area_sqm.required` | "The property area is required." | Area field empty |
| `properties.validation.area_sqm.numeric` | "The property area must be a number." | Area not numeric |
| `properties.validation.area_sqm.min` | "The property area must be at least 0 square meters." | Area negative |
| `properties.validation.area_sqm.max` | "The property area cannot exceed 10,000 square meters." | Area too large |
| `properties.validation.building_id.exists` | "The selected building does not exist." | Invalid building ID |

### Labels

| Key | English Label | Usage |
|-----|--------------|-------|
| `properties.labels.address` | "Address" | Form field label |
| `properties.labels.type` | "Property Type" | Form field label |
| `properties.labels.area` | "Area (m²)" | Form field label |
| `properties.labels.building` | "Building" | Form field label |
| `properties.labels.current_tenant` | "Current Tenant" | Form field label |

### Helper Text

| Key | English Text | Usage |
|-----|-------------|-------|
| `properties.helper_text.address` | "Full street address including building and apartment number" | Form field help |
| `properties.helper_text.type` | "Select apartment or house" | Form field help |
| `properties.helper_text.area` | "Property area in square meters (max 2 decimal places)" | Form field help |

---

## Request Examples

### Create Property (Filament)

**Endpoint**: Internal Filament form submission

**Request Payload**:
```json
{
  "address": "Gedimino pr. 1-23, Vilnius",
  "type": "apartment",
  "area_sqm": 45.50,
  "building_id": 123,
  "tenants": [456]
}
```

**Success Response** (Redirect):
```
HTTP/1.1 302 Found
Location: /admin/properties
```

**Validation Error Response**:
```json
{
  "message": "The property address is required. (and 1 more error)",
  "errors": {
    "address": [
      "The property address is required."
    ],
    "area_sqm": [
      "The property area must be a number."
    ]
  }
}
```

### Update Property (Filament)

**Endpoint**: Internal Filament form submission

**Request Payload**:
```json
{
  "address": "Updated Address",
  "type": "house",
  "area_sqm": 120.00,
  "building_id": null,
  "tenants": []
}
```

**Success Response** (Redirect):
```
HTTP/1.1 302 Found
Location: /admin/properties/{id}/edit
```

---

## Localization Support

### Supported Locales

- **English** (`en`): `lang/en/properties.php`
- **Lithuanian** (`lt`): `lang/lt/properties.php`
- **Russian** (`ru`): `lang/ru/properties.php`

### Locale Detection

Locale is determined by:
1. User preference (stored in session)
2. Application default (`config/app.php`)
3. Fallback to English

### Example: Lithuanian Validation

**Request with locale `lt`**:
```json
{
  "address": "",
  "type": "invalid"
}
```

**Response**:
```json
{
  "message": "Nuosavybės adresas yra privalomas. (ir dar 1 klaida)",
  "errors": {
    "address": [
      "Nuosavybės adresas yra privalomas."
    ],
    "type": [
      "Nuosavybės tipas turi būti butas arba namas."
    ]
  }
}
```

---

## Security Considerations

### Tenant Scope Enforcement

All property operations are automatically scoped to the authenticated user's tenant:

```php
// Automatic tenant scope via TenantScope
Property::query()->get();  // Only returns properties for current tenant

// Explicit tenant scope in Filament
->relationship(
    name: 'building',
    modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
)
```

### Authorization Checks

Before any property operation:

1. **Authentication**: User must be logged in
2. **Authorization**: Policy checks via `PropertyPolicy`
3. **Tenant Scope**: Data filtered by `tenant_id`
4. **Role Check**: Navigation hidden from tenant users

### Input Sanitization

- All input automatically escaped by Laravel
- Filament escapes output in forms/tables
- No raw HTML allowed in validation messages
- XSS protection via Content Security Policy

---

## Testing

### Validation Testing

```php
// Test required validation
$response = $this->post('/admin/properties', [
    'address' => '',
    'type' => 'apartment',
    'area_sqm' => 45.50,
]);

$response->assertSessionHasErrors(['address']);

// Test translation resolution
$messages = PropertyResource::getValidationMessages('address');
expect($messages['required'])->toBe(__('properties.validation.address.required'));
```

### Integration Testing

```bash
# Run all property validation tests
php artisan test --filter=PropertyResourceTranslationTest

# Test specific validation scenario
php artisan test --filter=test_address_validation
```

---

## Troubleshooting

### Common Issues

#### Issue: Validation messages in English despite locale setting

**Solution**:
1. Verify translation keys exist in target locale file
2. Clear translation cache: `php artisan optimize:clear`
3. Check locale is set: `app()->getLocale()`

#### Issue: Building validation fails for valid building

**Solution**:
1. Verify building belongs to user's tenant
2. Check `TenantScope` is applied
3. Ensure building exists in database

#### Issue: Area validation accepts invalid decimals

**Solution**:
1. Verify `step="0.01"` is set on form field
2. Check `decimal:0,2` rule is applied
3. Test with exact decimal values

---

## Best Practices

1. **Always use translation keys** for validation messages
2. **Test all validation rules** with edge cases
3. **Verify tenant scope** on all queries
4. **Clear caches** after translation changes
5. **Document custom validation rules** in this file
6. **Keep translation files in sync** across locales

---

## Related Documentation

- [PropertyResource Documentation](../filament/PROPERTY_RESOURCE_VALIDATION_LOCALIZATION.md)
- [HasTranslatedValidation Trait](../../app/Filament/Concerns/HasTranslatedValidation.php)
- [Property Model](../../app/Models/Property.php)
- [PropertyPolicy](../../app/Policies/PropertyPolicy.php)

---

**Maintained By**: Development Team  
**Review Cycle**: Quarterly  
**Next Review**: 2025-02-23
