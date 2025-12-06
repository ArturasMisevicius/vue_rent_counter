# Changelog: Gyvatukas Calculator Archival

**Date**: 2025-12-05  
**Type**: Business Decision / Architecture Change  
**Impact**: Medium - Affects billing calculation methodology

## Summary

The complex seasonal Gyvatukas (circulation fee) calculation logic has been archived and replaced with a simpler manual entry system. The sophisticated thermodynamic calculation engine is preserved in `_archive/Gyvatukas_Complex_Logic/` for potential future use.

## Changes

### Archived Components

1. **Service Layer**
   - `GyvatukasCalculator.php` - Main calculation service
   - `GyvatukasCalculatorService.php` - Alternative implementation
   - `GyvatukasCalculatorSecure.php` - Secure version with input validation
   - `GyvatukasCalculatorPolicy.php` - Authorization policy

2. **Configuration**
   - `config/gyvatukas.php` - Calculation parameters
   - Seasonal thresholds (heating season: Oct-Apr)
   - Physical constants (water specific heat, temperature delta)

3. **Test Suite**
   - 43 comprehensive tests covering all scenarios
   - Property-based tests for edge cases
   - Performance benchmarks
   - Security audit tests

4. **Documentation**
   - API reference documentation
   - Implementation guides
   - Performance optimization reports
   - Architecture diagrams

### New Implementation

**Before (Automated Calculation):**
```php
use App\Services\GyvatukasCalculator;

$calculator = new GyvatukasCalculator();
$circulationEnergy = $calculator->calculate($building, $billingMonth);
$cost = $circulationEnergy * $tariffRate;
$distribution = $calculator->distributeCirculationCost($building, $cost, 'area');
```

**After (Manual Entry):**
```php
// Landlord enters flat fee in building settings
$cost = $building->gyvatukas_monthly_fee ?? 0;

// Simple distribution (if needed)
$costPerProperty = $cost / $building->properties->count();
```

## Rationale

### Business Reasons

1. **Complexity vs. Value**: The sophisticated calculation required extensive meter reading data and complex seasonal logic that was difficult for users to understand and verify.

2. **Data Quality Issues**: Accurate calculations depended on complete meter reading data, which was not always available or reliable.

3. **User Preference**: Landlords preferred to manually set a flat monthly fee based on their own calculations or agreements with tenants.

4. **Maintenance Burden**: The complex logic required ongoing maintenance and support, with frequent questions about calculation methodology.

### Technical Considerations

1. **Simplified Data Model**: No longer requires `gyvatukas_summer_average` column or complex meter reading queries.

2. **Reduced Query Load**: Eliminates N+1 query patterns and complex eager loading requirements.

3. **Easier Testing**: Manual entry is straightforward to test compared to thermodynamic calculations.

4. **Better Performance**: No complex calculations or database queries during invoice generation.

## Migration Path

### Database Changes

**Optional Migration** (if reverting to automated):
```php
Schema::table('buildings', function (Blueprint $table) {
    $table->decimal('gyvatukas_summer_average', 10, 2)->nullable();
});
```

**Current Schema** (manual entry):
```php
Schema::table('buildings', function (Blueprint $table) {
    $table->decimal('gyvatukas_monthly_fee', 10, 2)->default(0);
});
```

### Data Migration

No data migration required. Existing `gyvatukas_summer_average` values are preserved but unused.

## Impact Assessment

### Affected Components

1. **BillingService**
   - Simplified gyvatukas calculation logic
   - Removed dependency on GyvatukasCalculator
   - Direct use of `$building->gyvatukas_monthly_fee`

2. **Invoice Generation**
   - Faster invoice generation (no complex calculations)
   - More predictable results
   - Easier to audit and verify

3. **User Interface**
   - New building settings field for manual fee entry
   - Removed complex calculation explanations
   - Simplified invoice line items

4. **Testing**
   - Removed 43 calculation tests
   - Added simple validation tests for manual entry
   - Reduced test suite execution time

### Performance Impact

**Before:**
- Complex calculation: ~50-100ms per building
- Database queries: 2-5 queries per building
- Memory usage: Caching required for large batches

**After:**
- Simple retrieval: <1ms per building
- Database queries: 0 additional queries (included in building fetch)
- Memory usage: Negligible

### User Experience Impact

**Positive:**
- Simpler to understand and verify
- Landlords have full control over fees
- Faster invoice generation
- No dependency on meter reading completeness

**Negative:**
- Manual entry required (additional admin work)
- No automatic seasonal adjustments
- Potential for inconsistent pricing across buildings

## Rollback Plan

If business requirements change back to automated calculations:

### Step 1: Restore Service
```bash
# Move from archive back to app/Services
cp _archive/Gyvatukas_Complex_Logic/GyvatukasCalculator.php app/Services/
```

### Step 2: Restore Configuration
```bash
# Restore configuration file
cp _archive/Gyvatukas_Complex_Logic/config/gyvatukas.php config/
```

### Step 3: Database Migration
```bash
# Ensure summer average column exists
php artisan migrate
```

### Step 4: Populate Summer Averages
```bash
# Run command to calculate summer averages for all buildings
php artisan gyvatukas:calculate-summer-averages 2024
```

### Step 5: Update BillingService
```php
// Restore GyvatukasCalculator dependency
public function __construct(
    private GyvatukasCalculator $gyvatukasCalculator
) {}

// Use calculator instead of manual fee
$circulationEnergy = $this->gyvatukasCalculator->calculate($building, $billingMonth);
```

### Step 6: Restore Tests
```bash
# Move tests from archive
cp _archive/Gyvatukas_Complex_Logic/tests/* tests/Unit/Services/
```

### Step 7: Update Documentation
```bash
# Mark as active in documentation
# Update README.md to remove "ARCHIVED" markers
```

## Documentation Updates

### New Documentation

1. **Archive Documentation**
   - `docs/services/GYVATUKAS_CALCULATOR_ARCHIVED.md` - Complete service documentation
   - `docs/api/GYVATUKAS_CALCULATOR_API.md` - API reference (marked as archived)
   - `_archive/Gyvatukas_Complex_Logic/README.txt` - Archive context

2. **Manual Entry Guide**
   - Building settings documentation
   - Manual fee entry workflow
   - Best practices for fee calculation

### Updated Documentation

1. **docs/README.md**
   - Marked Gyvatukas section as archived
   - Added links to archived documentation

2. **BillingService Documentation**
   - Updated to reflect manual entry approach
   - Removed references to GyvatukasCalculator

3. **Invoice Documentation**
   - Updated gyvatukas line item explanation
   - Simplified calculation methodology

## Testing Changes

### Removed Tests

- 43 GyvatukasCalculator unit tests
- 12 integration tests with BillingService
- 8 performance benchmark tests
- 6 security audit tests

**Total**: 69 tests removed

### New Tests

- 3 manual entry validation tests
- 2 building settings tests
- 1 invoice generation test

**Total**: 6 tests added

**Net Change**: -63 tests, ~5 minutes faster test suite execution

## Configuration Changes

### Removed Configuration

**File**: `config/gyvatukas.php`

```php
// No longer used
return [
    'water_specific_heat' => 1.163,
    'temperature_delta' => 45.0,
    'heating_season_start_month' => 10,
    'heating_season_end_month' => 4,
];
```

### New Configuration

**File**: `config/billing.php` (updated)

```php
'gyvatukas' => [
    'enabled' => true,
    'manual_entry' => true, // New flag
    'default_fee' => 0, // Default monthly fee
],
```

## Code Examples

### Before: Complex Calculation

```php
use App\Services\GyvatukasCalculator;
use App\Models\Building;
use Carbon\Carbon;

class BillingService
{
    public function __construct(
        private GyvatukasCalculator $gyvatukasCalculator
    ) {}
    
    public function calculateGyvatukas(Building $building, Carbon $billingMonth)
    {
        // Determine season
        if ($this->gyvatukasCalculator->isHeatingSeason($billingMonth)) {
            // Use summer average
            $circulationEnergy = $this->gyvatukasCalculator->calculateWinterGyvatukas($building);
        } else {
            // Calculate from meter readings
            $circulationEnergy = $this->gyvatukasCalculator->calculateSummerGyvatukas(
                $building, 
                $billingMonth
            );
        }
        
        // Get tariff rate
        $tariffRate = $this->getTariffRate('circulation', $billingMonth);
        
        // Calculate cost
        $totalCost = $circulationEnergy * $tariffRate;
        
        // Distribute among properties
        $distribution = $this->gyvatukasCalculator->distributeCirculationCost(
            $building,
            $totalCost,
            $building->distribution_method ?? 'equal'
        );
        
        return $distribution;
    }
}
```

### After: Simple Manual Entry

```php
use App\Models\Building;
use Carbon\Carbon;

class BillingService
{
    public function calculateGyvatukas(Building $building, Carbon $billingMonth)
    {
        // Get manual fee from building settings
        $totalCost = $building->gyvatukas_monthly_fee ?? 0;
        
        // Simple equal distribution
        $propertyCount = $building->properties->count();
        $costPerProperty = $propertyCount > 0 ? $totalCost / $propertyCount : 0;
        
        // Create distribution array
        $distribution = [];
        foreach ($building->properties as $property) {
            $distribution[$property->id] = $costPerProperty;
        }
        
        return $distribution;
    }
}
```

## Related Changes

### Pull Requests
- PR #XXX: Archive Gyvatukas complex logic
- PR #XXX: Implement manual entry system
- PR #XXX: Update BillingService integration
- PR #XXX: Update documentation

### Issues
- Issue #XXX: Simplify Gyvatukas calculation
- Issue #XXX: User feedback on calculation complexity
- Issue #XXX: Data quality issues with meter readings

## Future Considerations

### Potential Enhancements

1. **Hybrid Approach**: Allow buildings to choose between automated and manual calculation
2. **Calculation Templates**: Provide pre-configured calculation templates for common scenarios
3. **Historical Data**: Preserve historical calculation data for analysis
4. **Audit Trail**: Track changes to manual fee entries

### Monitoring

Monitor the following metrics after deployment:

1. **User Adoption**: Percentage of buildings with manual fees entered
2. **Data Quality**: Consistency of manual fee entries
3. **Support Tickets**: Reduction in calculation-related support requests
4. **Performance**: Invoice generation time improvements

## Conclusion

The archival of the Gyvatukas complex calculation logic represents a strategic decision to prioritize simplicity and user control over automated sophistication. The archived implementation remains available for future use if business requirements change.

**Key Benefits:**
- Simplified user experience
- Reduced maintenance burden
- Improved performance
- Greater user control

**Preserved Value:**
- Complete implementation archived
- Comprehensive documentation maintained
- Easy rollback path if needed
- Knowledge preserved for future reference

---

**Archived By**: Development Team  
**Archive Date**: 2025-12-05  
**Archive Location**: `_archive/Gyvatukas_Complex_Logic/`  
**Documentation**: `docs/services/GYVATUKAS_CALCULATOR_ARCHIVED.md`
