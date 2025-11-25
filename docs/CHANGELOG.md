# Changelog

All notable changes to the Vilnius Utilities Billing System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### Gyvatukas Summer Average Calculation System (2024-11-25)

**Command Enhancements**:
- Added `--force` option to force recalculation even if already calculated
- Added `--building` option to calculate for a specific building only
- Added input validation for year and building ID options
- Added comprehensive error handling with clear error messages
- Added progress bar for visual feedback during batch processing
- Added structured logging with full context (building ID, year, average, errors)

**Service Layer Pattern**:
- Created `GyvatukasSummerAverageService` to handle business logic
- Implemented dependency injection in `CalculateSummerAverageCommand`
- Separated concerns: command handles I/O, service handles business logic
- Added transaction support for atomic operations
- Implemented chunked processing for scalability (100 buildings per chunk)

**Value Objects**:
- Created `SummerPeriod` value object for date range encapsulation
  - Configuration-driven summer period (May-September by default)
  - Year validation (2020 - current year)
  - Factory methods: `forPreviousYear()`, `forCurrentYear()`
  - Immutable with readonly properties
- Created `CalculationResult` value object for calculation outcomes
  - Three states: success, skipped, failed
  - Type-safe result handling
  - Formatted message generation
  - Pattern matching support

**Documentation**:
- Added comprehensive command documentation (`docs/commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md`)
- Added service layer documentation (`docs/services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md`)
- Added value object documentation:
  - `docs/value-objects/SUMMER_PERIOD.md`
  - `docs/value-objects/CALCULATION_RESULT.md`
- Added API reference (`docs/api/GYVATUKAS_SUMMER_AVERAGE_API.md`)
- Added refactoring summary (`docs/refactoring/CALCULATE_SUMMER_AVERAGE_COMMAND_REFACTORING.md`)

**Testing**:
- Added `GyvatukasSummerAverageServiceTest` (9 tests, 100% coverage)
- Added `SummerPeriodTest` (7 tests, 100% coverage)
- Added `CalculationResultTest` (6 tests, 100% coverage)
- Total: 22 new tests with comprehensive coverage

**Configuration**:
- Added `gyvatukas.summer_start_month` configuration (default: 5)
- Added `gyvatukas.summer_end_month` configuration (default: 9)
- Added `gyvatukas.validation.min_year` configuration (default: 2020)
- Added `gyvatukas.audit.enabled` configuration (default: true)

### Changed

#### Building Model (2024-11-25)
- Fixed `calculateSummerAverage()` return type from `string` to `float`
- Fixed `getDisplayNameAttribute()` to handle null values gracefully
- Improved PHPDoc with requirement traceability (4.4)

#### CalculateSummerAverageCommand (2024-11-25)
- Refactored from 155 lines to 180 lines with better separation of concerns
- Reduced cyclomatic complexity from ~15 to ~3-4 per method
- Made class `final` for performance optimization
- Extracted methods:
  - `processSingleBuilding()`: Handles single building calculation
  - `processAllBuildings()`: Handles bulk processing with chunking
  - `displayResult()`: Formats and displays individual results
  - `displaySummary()`: Shows final statistics
  - `getYear()`: Validates and returns year
  - `getBuildingId()`: Validates and returns building ID

### Fixed

#### Gyvatukas Calculation (2024-11-25)
- Fixed skip logic to properly check if building already calculated for the year
- Fixed error handling to continue processing other buildings on individual failures
- Fixed logging to respect `gyvatukas.audit.enabled` configuration
- Fixed display name handling for buildings without names

### Performance

#### Memory Efficiency (2024-11-25)
- Implemented chunked processing (100 buildings per chunk)
- Constant memory usage regardless of building count
- Suitable for 10,000+ buildings without memory exhaustion

#### Query Optimization (2024-11-25)
- Single query per chunk instead of N+1 queries
- Transaction support for atomic operations
- Efficient skip logic with minimal database queries

### Security

#### Input Validation (2024-11-25)
- Added year validation (numeric, within acceptable range)
- Added building ID validation (positive integer)
- Added exception handling for invalid inputs
- Clear error messages for validation failures

### Code Quality

#### SOLID Principles (2024-11-25)
- **Single Responsibility**: Each class/method has one reason to change
- **Open/Closed**: Extensible without modification
- **Liskov Substitution**: Value objects are immutable and substitutable
- **Interface Segregation**: Focused, cohesive interfaces
- **Dependency Inversion**: Depends on abstractions (injected service)

#### Type Safety (2024-11-25)
- Added `declare(strict_types=1)` to all new files
- Full type hints on all methods
- Readonly properties where appropriate
- Comprehensive PHPDoc annotations

#### Testing (2024-11-25)
- 100% test coverage for new components
- Property-based testing patterns
- Comprehensive edge case coverage
- Integration with existing test suite

## [1.0.0] - 2024-11-20

### Initial Release

- Multi-tenant utilities billing system
- Hierarchical user management (superadmin, admin, manager, tenant)
- Subscription-based access control
- Complex billing calculations (electricity, water, heating, gyvatukas)
- Filament 4 admin panel
- Laravel 12 framework
- Comprehensive test suite

---

## Documentation References

### Command Documentation
- [Calculate Summer Average Command](commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)

### Service Documentation
- [Gyvatukas Summer Average Service](services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)

### Value Object Documentation
- [Summer Period](value-objects/SUMMER_PERIOD.md)
- [Calculation Result](value-objects/CALCULATION_RESULT.md)

### API Documentation
- [Gyvatukas Summer Average API](api/GYVATUKAS_SUMMER_AVERAGE_API.md)

### Refactoring Documentation
- [Calculate Summer Average Command Refactoring](refactoring/CALCULATE_SUMMER_AVERAGE_COMMAND_REFACTORING.md)

---

## Version Numbering

This project uses [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality additions
- **PATCH** version for backwards-compatible bug fixes

## Contributing

When adding entries to this changelog:

1. Add new entries under `[Unreleased]` section
2. Use categories: Added, Changed, Deprecated, Removed, Fixed, Security
3. Include date in format (YYYY-MM-DD)
4. Link to relevant documentation
5. Include requirement references where applicable
6. Note breaking changes clearly

## Links

- [Project Documentation](README.md)
- [Setup Guide](guides/SETUP.md)
- [Testing Guide](guides/TESTING_GUIDE.md)
- [Upgrade Guide](upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)
