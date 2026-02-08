# Scripts Documentation

This directory contains documentation for utility scripts used in the Vilnius Utilities Billing Platform project.

## Available Scripts

### Administrative Scripts

#### assign-super-admin.php

Assigns super admin privileges to the latest created user in the system.

**Location**: `assign-super-admin.php` (project root)

**Documentation**: [assign-super-admin.md](assign-super-admin.md)

**Quick Start**:
```bash
php assign-super-admin.php
```

**Purpose**:
- Grant super admin access during system deployment
- Restore admin access in emergency situations
- Set up admin users for development/testing
- Handle initial production setup

**Security Level**: 🔴 **HIGH RISK** - Grants unrestricted system access

---

### Namespace Consolidation

#### count-filament-imports.php

Analyzes Filament resources to assess namespace import patterns and identify consolidation opportunities.

**Location**: `scripts/count-filament-imports.php`

**Documentation**:
- [API Documentation](COUNT_FILAMENT_IMPORTS_API.md) - Comprehensive technical reference
- [Usage Guide](COUNT_FILAMENT_IMPORTS_USAGE.md) - Practical usage examples and workflows

**Quick Start**:
```bash
php scripts/count-filament-imports.php
```

**Purpose**:
- Quantify namespace consolidation progress
- Identify resources requiring consolidation
- Estimate effort for remaining work
- Track consolidation benefits

**Output**: Comprehensive report with:
- Summary statistics
- Detailed resource analysis
- Priority recommendations
- Effort estimation

---

#### verify-batch4-resources.php

Verifies that Batch 4 resources (FaqResource, LanguageResource, TranslationResource) are properly configured for Filament 4.

**Location**: `scripts/verify-batch4-resources.php`

**Quick Start**:
```bash
php scripts/verify-batch4-resources.php
```

**Verification Checks**:
- Class structure and configuration
- Model binding
- Icon configuration
- Page registration
- Filament 4 Schema API usage
- Namespace consolidation

---

#### verify-tariff-namespace-consolidation.php

Verifies namespace consolidation specifically for TariffResource.

**Location**: `scripts/verify-tariff-namespace-consolidation.php`

**Quick Start**:
```bash
php scripts/verify-tariff-namespace-consolidation.php
```

---

### Performance Verification

#### verify-tariff-performance.php

Verifies performance optimizations in TariffResource.

**Location**: `scripts/verify-tariff-performance.php`

**Quick Start**:
```bash
php scripts/verify-tariff-performance.php
```

---

### General Verification

#### verify-all-resources-namespace.php

Verifies namespace consolidation across all Filament resources.

**Location**: `scripts/verify-all-resources-namespace.php`

**Quick Start**:
```bash
php scripts/verify-all-resources-namespace.php
```

---

## Script Categories

### Analysis Scripts

Scripts that analyze code and generate reports:

- `count-filament-imports.php` - Import pattern analysis

### Verification Scripts

Scripts that verify implementation correctness:

- `verify-batch4-resources.php` - Batch 4 verification
- `verify-tariff-namespace-consolidation.php` - Tariff consolidation verification
- `verify-tariff-performance.php` - Performance verification
- `verify-all-resources-namespace.php` - Global namespace verification

## Common Workflows

### 1. Namespace Consolidation Project

```bash
# Step 1: Analyze current state
php scripts/count-filament-imports.php > reports/initial-assessment.txt

# Step 2: Consolidate resources (manual work)

# Step 3: Verify consolidation
php scripts/verify-all-resources-namespace.php

# Step 4: Track progress
php scripts/count-filament-imports.php > reports/progress-$(date +%Y%m%d).txt
```

### 2. Pre-Merge Verification

```bash
# Verify namespace consolidation
php scripts/count-filament-imports.php

# Verify specific batch
php scripts/verify-batch4-resources.php

# Verify performance
php scripts/verify-tariff-performance.php
```

### 3. Quality Gate

```bash
# Run all verification scripts
php scripts/verify-all-resources-namespace.php
php scripts/verify-batch4-resources.php
php scripts/verify-tariff-performance.php

# Check for issues
# All scripts should report success
```

## Integration with Development Process

### During Development

1. Run analysis scripts before starting work
2. Make changes
3. Run verification scripts after changes
4. Update documentation

### During Code Review

1. Reviewer runs verification scripts
2. Check for regressions
3. Verify new code follows standards
4. Approve if all checks pass

### In CI/CD Pipeline

```yaml
# Example GitHub Actions workflow
- name: Run Verification Scripts
  run: |
    php scripts/verify-all-resources-namespace.php
    php scripts/verify-batch4-resources.php
    php scripts/verify-tariff-performance.php
```

## Script Development Guidelines

### Creating New Scripts

1. **Location**: Place in `scripts/` directory
2. **Naming**: Use descriptive kebab-case names
3. **Documentation**: Create corresponding docs in `docs/scripts/`
4. **Header**: Include comprehensive DocBlock
5. **Output**: Provide clear, actionable output
6. **Exit codes**: Use standard exit codes (0 = success, 1 = failure)

### Documentation Requirements

For each script, create:

1. **API Documentation**: Technical reference (`*_API.md`)
   - Purpose and overview
   - Usage instructions
   - Output format
   - Data structures
   - Integration points

2. **Usage Guide**: Practical examples (`*_USAGE.md`)
   - Quick start
   - Common workflows
   - Examples
   - Troubleshooting

### Script Template

```php
<?php

/**
 * Script Name - Brief Description
 * 
 * Detailed description of what the script does,
 * why it exists, and how it should be used.
 * 
 * Purpose:
 * - Purpose point 1
 * - Purpose point 2
 * 
 * Usage:
 *   php scripts/script-name.php
 * 
 * Related Documentation:
 * - docs/scripts/SCRIPT_NAME_API.md
 * - docs/scripts/SCRIPT_NAME_USAGE.md
 * 
 * @package VilniusBilling\Scripts
 * @author  [Author Name]
 * @version 1.0.0
 * @since   YYYY-MM-DD
 */

// Script implementation
```

## Related Documentation

### Project Documentation

- [Namespace Consolidation Tasks](../tasks/tasks.md)
- [Namespace Consolidation Assessment](../filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md)
- [Filament 4 Migration Guide](../upgrades/FILAMENT_V4_MIGRATION.md)

### Quality Standards

- [Quality Playbook](../.kiro/steering/quality.md)
- [Operating Principles](../.kiro/steering/operating-principles.md)

## Contributing

When adding new scripts:

1. Follow naming conventions
2. Create comprehensive documentation
3. Add entry to this README
4. Update related task documentation
5. Test thoroughly before committing

## Support

For questions or issues with scripts:

1. Check script documentation in `docs/scripts/`
2. Review related task documentation in `.kiro/specs/`
3. Consult project documentation in `docs/`

## Version History

- **2024-11-29**: Added count-filament-imports.php documentation
- **2024-11-XX**: Initial scripts directory documentation
