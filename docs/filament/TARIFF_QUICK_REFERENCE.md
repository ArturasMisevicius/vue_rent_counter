# Tariff Management Quick Reference

## Creating Tariffs

### Manual Entry Mode (No Provider)

**When to use:**
- Historical data from paper records
- Custom tariff configurations
- Testing and development
- Temporary promotional rates

**Steps:**
1. Navigate to **Tariffs → Create**
2. Enable **"Manual Entry Mode"** toggle
3. Enter tariff name
4. Configure tariff type and rates
5. Set active period
6. Save

**Result:** Tariff created with `provider_id = null`

### Provider Integration Mode (With Provider)

**When to use:**
- Automated provider sync
- External system integration
- Provider-managed rates
- Audit trail requirements

**Steps:**
1. Navigate to **Tariffs → Create**
2. Keep **"Manual Entry Mode"** disabled (default)
3. Select provider from dropdown
4. Optionally enter **External System ID**
5. Enter tariff name
6. Configure tariff type and rates
7. Set active period
8. Save

**Result:** Tariff created with `provider_id` and optional `remote_id`

## Field Reference

| Field | Required | Manual Mode | Provider Mode | Description |
|-------|----------|-------------|---------------|-------------|
| Manual Entry Mode | No | N/A | N/A | Toggle to enable/disable manual mode |
| Provider | Conditional | Hidden | Required | Provider selection |
| External System ID | No | Hidden | Optional | External system identifier |
| Tariff Name | Yes | Visible | Visible | Human-readable name |
| Tariff Type | Yes | Visible | Visible | Flat or Time-of-Use |
| Active From | Yes | Visible | Visible | Start date |
| Active Until | No | Visible | Visible | End date (optional) |

## Tariff Types

### Flat Rate

**Configuration:**
```json
{
  "type": "flat",
  "rate": 0.15,
  "currency": "EUR",
  "fixed_fee": 5.00
}
```

**Use case:** Single rate for all consumption

### Time-of-Use

**Configuration:**
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

**Use case:** Different rates for different times of day

## Common Tasks

### Check if Tariff is Manual

```php
$tariff = Tariff::find($id);
if ($tariff->isManual()) {
    // This is a manual tariff
}
```

### Find Active Tariffs

```php
$activeTariffs = Tariff::active()->get();
```

### Find Provider Tariffs

```php
$providerTariffs = Tariff::forProvider($providerId)->get();
```

### Convert Manual to Provider-Linked

1. Edit the manual tariff
2. Disable manual mode toggle
3. Select provider
4. Optionally add external system ID
5. Save

## Validation Rules

### Manual Mode

- ✅ Provider: Not required
- ✅ External ID: Not visible
- ✅ Name: Required, max 255 chars
- ✅ Configuration: Required
- ✅ Active From: Required

### Provider Mode

- ✅ Provider: Required
- ✅ External ID: Optional, max 255 chars
- ✅ Name: Required, max 255 chars
- ✅ Configuration: Required
- ✅ Active From: Required

## Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| "Provider is required" | Provider not selected in provider mode | Select a provider or enable manual mode |
| "External ID may not be greater than 255 characters" | Remote ID too long | Shorten external ID to 255 chars or less |
| "Tariff name contains invalid characters" | Name has special characters | Use only letters, numbers, spaces, and basic punctuation |
| "Rate is required for flat tariffs" | Missing rate in flat tariff | Enter a rate value |
| "At least one zone is required" | No zones in time-of-use tariff | Add at least one time zone |

## API Examples

### Create Manual Tariff

```bash
POST /api/tariffs
{
  "provider_id": null,
  "name": "Manual Historical Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.12,
    "currency": "EUR"
  },
  "active_from": "2024-01-01"
}
```

### Create Provider Tariff

```bash
POST /api/tariffs
{
  "provider_id": 5,
  "remote_id": "EXT-12345",
  "name": "Provider Standard Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.15,
    "currency": "EUR"
  },
  "active_from": "2025-01-01"
}
```

## Troubleshooting

### Issue: Can't see manual mode toggle

**Cause:** Insufficient permissions

**Solution:** Ensure you have SUPERADMIN or ADMIN role

### Issue: Provider field still required in manual mode

**Cause:** Manual mode toggle not enabled

**Solution:** Click the manual mode toggle to enable it

### Issue: Can't save tariff with external ID but no provider

**Cause:** Validation requires provider when external ID provided

**Solution:** Either select a provider or remove the external ID

### Issue: Manual tariff not appearing in provider filter

**Cause:** Manual tariffs have no provider

**Solution:** Use "All Tariffs" filter or filter by tariff type instead

## Best Practices

1. **Use Manual Mode For:**
   - Historical data entry
   - One-time custom rates
   - Testing scenarios

2. **Use Provider Mode For:**
   - Ongoing provider relationships
   - Automated rate updates
   - External system integration

3. **Naming Conventions:**
   - Include date range in name for historical tariffs
   - Include provider name for provider tariffs
   - Use descriptive names for easy identification

4. **External System IDs:**
   - Use consistent format across all tariffs
   - Document ID format in provider notes
   - Keep IDs under 255 characters

5. **Active Periods:**
   - Set end dates for historical tariffs
   - Leave end date empty for current tariffs
   - Don't overlap active periods for same provider

## Related Documentation

- [Detailed Feature Guide](./TARIFF_MANUAL_MODE.md)
- [API Documentation](../api/TARIFF_API.md)
- [Architecture Documentation](../architecture/TARIFF_MANUAL_MODE_ARCHITECTURE.md)
- [Testing Guide](../testing/TARIFF_TESTING.md)
