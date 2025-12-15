# Console Commands Documentation

This directory contains comprehensive documentation for all Artisan console commands in the Vilnius Utilities Billing System.

## Available Commands

### hot water circulation Calculations

#### Calculate Summer Average

**Command**: `php artisan hot water circulation:calculate-summer-average`

**Purpose**: Calculates and stores summer average hot water circulation (circulation fees) for buildings to establish baseline for winter billing.

**Documentation**: [CALCULATE_SUMMER_AVERAGE_COMMAND.md](CALCULATE_SUMMER_AVERAGE_COMMAND.md)

**Quick Reference**: [Quick Reference Guide](../reference/hot water circulation_SUMMER_AVERAGE_QUICK_REFERENCE.md)

**Related Documentation**:
- [Service Documentation](../services/hot water circulation_SUMMER_AVERAGE_SERVICE.md)
- [API Reference](../api/hot water circulation_SUMMER_AVERAGE_API.md)
- [Value Objects](../value-objects/SUMMER_PERIOD.md)

**Usage**:
```bash
# Calculate for all buildings (previous year)
php artisan hot water circulation:calculate-summer-average

# Calculate for specific year
php artisan hot water circulation:calculate-summer-average --year=2023

# Calculate for single building
php artisan hot water circulation:calculate-summer-average --building=42

# Force recalculation
php artisan hot water circulation:calculate-summer-average --force
```

**Scheduled**: October 1st at 2:00 AM (automatic)

**Requirements**: 4.4

---

### User Management

#### Migrate to Hierarchical Users

**Command**: `php artisan users:migrate-hierarchical`

**Purpose**: Migrates existing users to the new hierarchical user management system.

**Documentation**: *(To be created)*

**Usage**:
```bash
php artisan users:migrate-hierarchical
```

---

### Testing

#### Test Setup

**Command**: `php artisan test:setup`

**Purpose**: Sets up deterministic test data for the test suite.

**Documentation**: [Testing Guide](../guides/TESTING_GUIDE.md)

**Usage**:
```bash
# Fresh setup
php artisan test:setup --fresh

# Seed only
php artisan test:setup
```

---

### Notifications

#### Notify Overdue Invoices

**Command**: `php artisan invoices:notify-overdue`

**Purpose**: Sends notifications for overdue invoices.

**Documentation**: *(To be created)*

**Usage**:
```bash
php artisan invoices:notify-overdue
```

---

## Command Categories

### Billing & Calculations
- `hot water circulation:calculate-summer-average` - Calculate summer average hot water circulation

### User Management
- `users:migrate-hierarchical` - Migrate to hierarchical users

### Notifications
- `invoices:notify-overdue` - Notify overdue invoices

### Testing & Development
- `test:setup` - Setup test data

## Documentation Standards

All command documentation includes:

### Required Sections
- **Overview**: Purpose and context
- **Command Signature**: Full signature with options
- **Usage Examples**: Multiple real-world examples
- **Options**: Detailed option descriptions
- **Output Format**: Expected output
- **Exit Codes**: Success/failure codes
- **Error Handling**: Common errors and solutions
- **Logging**: What gets logged
- **Configuration**: Related configuration
- **Testing**: How to test the command
- **Performance**: Performance considerations
- **Related Documentation**: Links to related docs

### Code Examples
- Complete, runnable examples
- Multiple usage scenarios
- Error handling patterns
- Best practices

### Troubleshooting
- Common issues
- Error messages
- Solutions
- Debugging tips

## Creating New Command Documentation

When documenting a new command:

1. **Create File**: `docs/commands/COMMAND_NAME.md`
2. **Use Template**: Follow existing command documentation structure
3. **Include All Sections**: See "Required Sections" above
4. **Add Examples**: Provide multiple usage examples
5. **Cross-Reference**: Link to related documentation
6. **Update Index**: Add entry to this README
7. **Update Changelog**: Add entry to [docs/CHANGELOG.md](../CHANGELOG.md)

### Template Structure

```markdown
# Command Name

## Overview
[Purpose and context]

## Command Signature
```bash
php artisan command:name [options]
```

## Options
[Table of options]

## Usage Examples
[Multiple examples]

## Output Format
[Expected output]

## Exit Codes
[Success/failure codes]

## Error Handling
[Common errors and solutions]

## Logging
[What gets logged]

## Configuration
[Related configuration]

## Testing
[How to test]

## Performance
[Performance considerations]

## Related Documentation
[Links to related docs]

## Changelog
[Version history]
```

## Quick Links

### Documentation
- [Main README](../../README.md)
- [Setup Guide](../guides/SETUP.md)
- [Testing Guide](../guides/TESTING_GUIDE.md)
- [API Documentation](../api/)
- [Service Documentation](../services/)

### Specifications
- [Vilnius Utilities Billing Spec](../../.kiro/specs/2-vilnius-utilities-billing/)
- [Tasks](../tasks/tasks.md)

### Testing
- [Test Suite](../../tests/)
- [Unit Tests](../../tests/Unit/)
- [Feature Tests](../../tests/Feature/)

## Contributing

When adding or updating command documentation:

1. Follow the documentation standards
2. Include complete, tested examples
3. Update this index
4. Update the changelog
5. Cross-reference related documentation
6. Review for consistency and clarity

## Support

For questions about commands:
- Review the command documentation
- Check the [Testing Guide](../guides/TESTING_GUIDE.md)
- Examine the command source code
- Run with `--help` flag for quick reference

## Version History

- **2024-11-25**: Added hot water circulation summer average command documentation
- **2024-11-20**: Initial command documentation structure
