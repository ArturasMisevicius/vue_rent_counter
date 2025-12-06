# Gyvatukas Complex Logic - Archive Summary

## Archive Date
2025-12-05

## Business Decision
Simplify Gyvatukas (Heated Towel Rail) handling from complex seasonal calculations to manual flat-fee entry.

## What Was Archived

### Service Classes (3 files)
1. **GyvatukasCalculator.php** - Main calculator with seasonal logic
2. **GyvatukasCalculatorService.php** - Service wrapper with authorization and audit
3. **GyvatukasCalculatorSecure.php** - Secure version with BCMath and enhanced security

### Policy (1 file)
4. **GyvatukasCalculatorPolicy.php** - Authorization policy for calculations

### Test Files (5 files)
5. **tests/Unit/GyvatukasCalculatorTest.php**
6. **tests/Unit/Services/GyvatukasCalculatorTest.php**
7. **tests/Unit/Services/GyvatukasCalculatorPerformanceTest.php**
8. **tests/Security/GyvatukasCalculatorSecurityTest.php**
9. **tests/Performance/GyvatukasCalculatorPerformanceTest.php**

### Configuration (1 file)
10. **config/gyvatukas.php** - Configuration with seasonal parameters

### Verification Script (1 file)
11. **verify-gyvatukas-calculator.php** - Standalone verification script

## Complex Logic Features (Preserved)

The archived calculator implemented:

### Seasonal Calculations
- **Heating Season** (October-April): Used stored summer average
- **Non-Heating Season** (May-September): Calculated using formula Q_circ = Q_total - (V_water × c × ΔT)

### Formula Components
- Q_circ = Circulation energy (kWh)
- Q_total = Total building heating energy consumption (kWh)
- V_water = Hot water volume consumption (m³)
- c = Specific heat capacity of water (1.163 kWh/m³·°C)
- ΔT = Temperature difference (45°C)

### Distribution Methods
- **Equal**: Divide cost equally among all apartments (C/N)
- **Area-based**: Divide proportionally by apartment area (C × A_i / Σ A_j)

### Performance Features
- Eager loading to prevent N+1 queries
- Multi-level caching (calculation + consumption)
- Selective column loading
- Query count monitoring

### Security Features
- Authorization via policy
- Rate limiting (10 per user/min, 100 per tenant/min)
- Audit trail for all calculations
- PII-safe logging with hashed identifiers
- BCMath for financial precision

## New Simplified Approach

### Manual Entry Model
Gyvatukas is now treated as a simple flat fee, similar to:
- Internet service
- Security service
- Other fixed-cost utilities

### Implementation
Landlords manually add Gyvatukas as an invoice line item:
- **Description**: "Gyvatukas" or "Heated Towel Rail"
- **Quantity**: 1
- **Unit**: "month" or "service"
- **Unit Price**: Manual entry (e.g., 15.00 EUR)
- **Total**: Calculated automatically

### Database Support
The existing `InvoiceItem` model already supports this:
```php
protected $fillable = [
    'invoice_id',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'total',
    'meter_reading_snapshot',
];
```

No database changes required - the flexible schema handles manual entries.

## Why This Change?

1. **Simplicity**: Removes complex seasonal calculations
2. **Flexibility**: Landlords can adjust costs as needed
3. **Transparency**: Clear, predictable costs
4. **Maintenance**: Less code to maintain and test
5. **Business Alignment**: Matches how other flat fees are handled

## Future Considerations

If business requirements change and automated calculations are needed again:
1. This archive contains all the working code
2. All tests are preserved (100+ test cases)
3. Configuration is documented
4. Can be restored and integrated back into the codebase

## Files Removed from Active Codebase

All files listed above have been:
- ✅ Moved to `_archive/Gyvatukas_Complex_Logic/`
- ✅ Removed from `app/` and `tests/` directories
- ✅ Removed from autoloader (composer dump-autoload executed)
- ✅ No remaining references in active codebase

## Verification Steps Completed

1. ✅ All Gyvatukas files moved to archive
2. ✅ Composer autoload regenerated
3. ✅ No references to GyvatukasCalculator in active PHP files
4. ✅ InvoiceItem model confirmed to support manual entries
5. ⏳ Test suite verification (next step)

## Next Steps

1. Run test suite to ensure no broken dependencies
2. Update documentation to reflect manual entry approach
3. Update Filament resources if needed to support manual Gyvatukas entry
4. Train users on new manual entry process
