# TariffResolver Service Implementation

## Overview

The TariffResolver service has been successfully implemented to handle temporal tariff selection and cost calculation for the Vilnius Utilities Billing System. The implementation follows the Strategy pattern for extensibility and maintainability.

## Implementation Details

### Core Service: `app/Services/TariffResolver.php`

The main service class provides two key methods:

1. **`resolve(Provider $provider, Carbon $date): Tariff`**
   - Selects the active tariff for a provider on a given date
   - Implements temporal logic using `active_from` and `active_until` dates
   - Returns the most recent tariff when multiple tariffs are active
   - Throws `ModelNotFoundException` if no active tariff exists

2. **`calculateCost(Tariff $tariff, float $consumption, ?Carbon $timestamp = null): float`**
   - Calculates the cost based on tariff type and consumption
   - Delegates to appropriate strategy based on tariff type
   - Supports flat rate and time-of-use tariffs
   - Uses current time if timestamp not provided

### Strategy Pattern Implementation

#### Interface: `app/Services/TariffCalculation/TariffCalculationStrategy.php`

Defines the contract for tariff calculation strategies:
- `calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float`
- `supports(string $tariffType): bool`

#### Flat Rate Strategy: `app/Services/TariffCalculation/FlatRateStrategy.php`

Handles simple flat rate tariffs:
- Multiplies consumption by the configured rate
- Configuration format: `{'type': 'flat', 'rate': 0.15}`

#### Time-of-Use Strategy: `app/Services/TariffCalculation/TimeOfUseStrategy.php`

Handles complex time-based pricing:
- Determines applicable zone based on timestamp
- Supports weekend logic (apply_night_rate, apply_day_rate, apply_weekend_rate)
- Handles midnight-crossing time ranges (e.g., 23:00 to 07:00)
- Configuration format:
  ```json
  {
    "type": "time_of_use",
    "zones": [
      {"id": "day", "start": "07:00", "end": "23:00", "rate": 0.18},
      {"id": "night", "start": "23:00", "end": "07:00", "rate": 0.10}
    ],
    "weekend_logic": "apply_night_rate"
  }
  ```

### Zone Determination Logic

The `determineZone()` method in `TimeOfUseStrategy`:

1. **Weekend Check**: If timestamp is on weekend and weekend_logic is defined:
   - `apply_night_rate`: Uses the 'night' zone
   - `apply_day_rate`: Uses the 'day' zone
   - `apply_weekend_rate`: Uses the 'weekend' zone

2. **Time-Based Selection**: Finds the zone where current time falls within start/end range
   - Handles normal ranges (e.g., 07:00 to 23:00)
   - Handles midnight-crossing ranges (e.g., 23:00 to 07:00)

3. **Fallback**: Returns first zone if no match found

## Supporting Models

### Tariff Model Enhancements

Added helper methods:
- `isActiveOn(Carbon $date): bool` - Check if tariff is active on a date
- `isFlatRate(): bool` - Check if tariff is flat rate
- `isTimeOfUse(): bool` - Check if tariff is time-of-use
- `getFlatRate(): ?float` - Get flat rate if applicable

Added query scopes:
- `active($date)` - Filter to active tariffs on a date
- `forProvider($providerId)` - Filter by provider
- `flatRate()` - Filter to flat rate tariffs
- `timeOfUse()` - Filter to time-of-use tariffs

## Testing

### Unit Tests: `tests/Unit/TariffResolverTest.php`

Comprehensive test coverage includes:

1. **Temporal Selection Tests**:
   - Returns active tariff for given date
   - Returns most recent tariff when multiple are active
   - Respects active_until date
   - Throws exception when no active tariff exists

2. **Cost Calculation Tests**:
   - Works with flat rate tariff
   - Works with time-of-use tariff during day
   - Works with time-of-use tariff during night
   - Applies weekend logic correctly
   - Handles midnight crossing time ranges
   - Uses current time when timestamp not provided

### Factory States: `database/factories/TariffFactory.php`

Added factory states for testing:
- `flat()` - Creates flat rate tariff
- `timeOfUse()` - Creates time-of-use tariff
- `ignitis()` - Creates Ignitis-specific tariff with weekend logic
- `activeFrom($date)` - Sets active_from date
- `activeUntil($date)` - Sets active_until date

## Requirements Validation

This implementation satisfies the following requirements from the spec:

- **Requirement 2.3**: Temporal tariff selection using active_from date ✅
- **Requirement 2.4**: Multiple tariff handling with most recent selection ✅
- **Requirement 2.5**: Weekend logic support for special rates ✅

## Correctness Properties

The implementation validates these correctness properties:

- **Property 7**: Tariff temporal selection - Correctly selects tariff where active_from ≤ billing_date AND (active_until IS NULL OR active_until ≥ billing_date)
- **Property 8**: Weekend tariff rate application - Correctly applies weekend rates based on weekend_logic configuration

## Usage Examples

### Resolving a Tariff

```php
$resolver = new TariffResolver();
$provider = Provider::find(1);
$date = Carbon::parse('2024-06-15');

$tariff = $resolver->resolve($provider, $date);
```

### Calculating Cost

```php
// Flat rate
$cost = $resolver->calculateCost($tariff, 100.0);

// Time-of-use with specific timestamp
$timestamp = Carbon::parse('2024-06-15 14:00:00');
$cost = $resolver->calculateCost($tariff, 100.0, $timestamp);
```

## Extensibility

The Strategy pattern allows easy addition of new tariff types:

1. Create a new strategy class implementing `TariffCalculationStrategy`
2. Add the strategy to the `TariffResolver` constructor
3. No changes needed to existing code

Example for a tiered rate tariff:
```php
class TieredRateStrategy implements TariffCalculationStrategy
{
    public function supports(string $tariffType): bool
    {
        return $tariffType === 'tiered';
    }
    
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float
    {
        // Implementation for tiered rates
    }
}
```

## Integration Points

The TariffResolver is used by:
- `BillingService` - For invoice generation with tariff snapshotting
- `InvoiceService` - For cost calculations
- Filament resources - For tariff management and preview

## Performance Considerations

- Tariff resolution uses indexed queries on `active_from` and `active_until`
- Strategy pattern avoids conditional logic in main service
- Zone determination is O(n) where n is number of zones (typically 2-3)

## Future Enhancements

Potential improvements:
- Cache frequently used tariffs
- Support for seasonal tariffs
- Support for tiered consumption rates
- Support for demand charges
- Historical tariff analysis tools

## Conclusion

The TariffResolver service is fully implemented with:
- ✅ Temporal tariff selection logic
- ✅ Flat rate calculation
- ✅ Time-of-use calculation with zone determination
- ✅ Weekend logic support
- ✅ Midnight-crossing time range handling
- ✅ Comprehensive unit tests
- ✅ Extensible architecture
- ✅ Factory support for testing

The implementation is production-ready and meets all requirements specified in the design document.
