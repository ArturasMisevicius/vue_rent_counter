# Count Filament Imports Script - Usage Guide

## Quick Start

### Basic Usage

```bash
# Run from project root
php scripts/count-filament-imports.php
```

### Expected Output

```
================================================================================
FILAMENT NAMESPACE CONSOLIDATION - IMPORT COUNT REPORT
================================================================================
Generated: 2024-11-29 10:30:45
Total Resources Analyzed: 14
================================================================================

SUMMARY
-------
✅ Already Consolidated: 3 resources
⏭️  Needs Consolidation: 11 resources

RESOURCES NEEDING CONSOLIDATION
-------------------------------
❌ TariffResource: 8 individual imports (Actions: 3, Columns: 4, Filters: 1)
❌ PropertyResource: 7 individual imports (Actions: 2, Columns: 4, Filters: 1)
...
```

## Common Workflows

### 1. Initial Project Assessment

**Goal**: Establish baseline for namespace consolidation project

```bash
# Step 1: Run analysis
php scripts/count-filament-imports.php > reports/initial-assessment.txt

# Step 2: Review high-priority resources
grep "Priority Resources" reports/initial-assessment.txt

# Step 3: Document findings
# Update .kiro/specs/6-filament-namespace-consolidation/tasks.md
```

**What to look for**:
- Total resources needing consolidation
- Resources with 5+ individual imports (high priority)
- Estimated total effort

### 2. Progress Tracking

**Goal**: Monitor consolidation progress over time

```bash
# Before consolidation work
php scripts/count-filament-imports.php > reports/before-$(date +%Y%m%d).txt

# After consolidation work
php scripts/count-filament-imports.php > reports/after-$(date +%Y%m%d).txt

# Compare results
diff reports/before-*.txt reports/after-*.txt
```

**Metrics to track**:
- Number of consolidated resources (increasing)
- Number needing consolidation (decreasing)
- Total individual imports (decreasing)

### 3. Pre-Merge Verification

**Goal**: Ensure new code follows consolidation standards

```bash
# Run before creating pull request
php scripts/count-filament-imports.php

# Check for new resources needing consolidation
# If any found, consolidate before merging
```

**Verification checklist**:
- [ ] All new resources use consolidated imports
- [ ] No increase in resources needing consolidation
- [ ] Report shows expected consolidation status

### 4. Batch Consolidation Planning

**Goal**: Plan consolidation work for multiple resources

```bash
# Generate report
php scripts/count-filament-imports.php > batch-plan.txt

# Extract high-priority resources
grep "Priority Resources" batch-plan.txt

# Create task list in tasks.md
```

**Planning steps**:
1. Identify resources with 5+ imports (highest priority)
2. Group related resources (e.g., all billing resources)
3. Estimate effort (30-60 min per resource)
4. Schedule consolidation work

## Reading the Report

### Summary Section

```
SUMMARY
-------
✅ Already Consolidated: 3 resources
⏭️  Needs Consolidation: 11 resources
```

**Interpretation**:
- **Already Consolidated**: Resources following best practices
- **Needs Consolidation**: Resources requiring updates

**Action**: Focus on "Needs Consolidation" count

### Resources Needing Consolidation

```
❌ TariffResource: 8 individual imports (Actions: 3, Columns: 4, Filters: 1)
```

**Breakdown**:
- **Resource name**: TariffResource
- **Total imports**: 8 individual imports
- **Actions**: 3 action imports (EditAction, DeleteAction, etc.)
- **Columns**: 4 column imports (TextColumn, IconColumn, etc.)
- **Filters**: 1 filter import (SelectFilter, etc.)

**Action**: Prioritize resources with highest import counts

### Detailed Breakdown

```
❌ TariffResource
-------------------
File: TariffResource.php
Status: NEEDS CONSOLIDATION
Individual Imports:
  - Actions:  3
  - Columns:  4
  - Filters:  1
  - Total:    8
Consolidated Import: NO ❌
Table Import: YES
Potential Reduction: 88.9% (8 → 1 import)
```

**Key metrics**:
- **Status**: Current consolidation state
- **Individual Imports**: Breakdown by type
- **Consolidated Import**: Whether `use Filament\Tables;` is present
- **Table Import**: Whether `use Filament\Tables\Table;` is present
- **Potential Reduction**: Code reduction benefit

**Action**: Note potential reduction percentage for prioritization

### Recommendations

```
Priority Resources (5+ individual imports):
  • TariffResource: 8 imports
  • PropertyResource: 7 imports
  • BuildingResource: 6 imports

Estimated Total Effort:
  • Resources to consolidate: 11
  • Estimated time per resource: 30-60 minutes
  • Total estimated time: 8.25 hours
```

**Planning guidance**:
- **Priority Resources**: Start with these (highest impact)
- **Estimated Effort**: Use for sprint planning
- **Time per resource**: Budget 30-60 minutes each

## Integration with Development Workflow

### During Development

```bash
# Before starting work
php scripts/count-filament-imports.php

# After creating new resource
php scripts/count-filament-imports.php

# Verify new resource is consolidated
```

### During Code Review

```bash
# Reviewer runs script
php scripts/count-filament-imports.php

# Checks:
# 1. No increase in resources needing consolidation
# 2. New resources use consolidated imports
# 3. Modified resources maintain consolidation
```

### In CI/CD Pipeline

```yaml
# .github/workflows/quality.yml
- name: Check Namespace Consolidation
  run: |
    php scripts/count-filament-imports.php > report.txt
    # Fail if new resources need consolidation
    if grep -q "NEEDS CONSOLIDATION" report.txt; then
      echo "New resources require namespace consolidation"
      exit 1
    fi
```

## Interpreting Results

### Scenario 1: All Resources Consolidated

```
✅ All resources are already consolidated!
   No further action needed.
```

**Meaning**: Project follows namespace consolidation best practices

**Action**: Maintain standards for new resources

### Scenario 2: Some Resources Need Work

```
⏭️  Needs Consolidation: 5 resources
```

**Meaning**: Consolidation work in progress or needed

**Action**: 
1. Review priority resources
2. Plan consolidation work
3. Update tasks.md

### Scenario 3: Many Resources Need Work

```
⏭️  Needs Consolidation: 20 resources
```

**Meaning**: Large consolidation project needed

**Action**:
1. Create consolidation project plan
2. Prioritize by import count
3. Schedule batch consolidation work
4. Track progress regularly

## Tips and Best Practices

### Tip 1: Run Regularly

```bash
# Add to daily workflow
alias check-imports='php scripts/count-filament-imports.php'

# Run after each consolidation
check-imports
```

### Tip 2: Save Historical Reports

```bash
# Create reports directory
mkdir -p reports/namespace-consolidation

# Save dated reports
php scripts/count-filament-imports.php > \
  reports/namespace-consolidation/$(date +%Y-%m-%d).txt
```

### Tip 3: Focus on High-Impact Resources

Priority order:
1. Resources with 8+ imports (highest impact)
2. Resources with 5-7 imports (high impact)
3. Resources with 3-4 imports (medium impact)
4. Resources with 1-2 imports (low impact)

### Tip 4: Batch Similar Resources

Group resources by domain:
- **Billing**: TariffResource, InvoiceResource, ProviderResource
- **Property**: PropertyResource, BuildingResource, MeterResource
- **User**: UserResource, SubscriptionResource

### Tip 5: Document Progress

Update tasks.md after each consolidation:

```markdown
## Completed Consolidations
- [x] TariffResource (2024-11-29) - 8 → 1 imports (88.9% reduction)
- [x] PropertyResource (2024-11-29) - 7 → 1 imports (85.7% reduction)
```

## Troubleshooting

### Issue: Script Not Found

```bash
# Error: No such file or directory
```

**Solution**: Run from project root

```bash
cd /path/to/project
php scripts/count-filament-imports.php
```

### Issue: No Resources Found

```bash
# Output: Total Resources Analyzed: 0
```

**Solution**: Verify resources directory exists

```bash
ls -la app/Filament/Resources/
```

### Issue: Unexpected Results

```bash
# Resource shows as needing consolidation but appears correct
```

**Solution**: Manually verify import statements

```bash
# Check actual imports
grep "^use Filament" app/Filament/Resources/ResourceName.php
```

## Examples

### Example 1: Fresh Project Assessment

```bash
$ php scripts/count-filament-imports.php

================================================================================
FILAMENT NAMESPACE CONSOLIDATION - IMPORT COUNT REPORT
================================================================================
Generated: 2024-11-29 10:30:45
Total Resources Analyzed: 14
================================================================================

SUMMARY
-------
✅ Already Consolidated: 3 resources
⏭️  Needs Consolidation: 11 resources

RESOURCES NEEDING CONSOLIDATION
-------------------------------
❌ TariffResource: 8 individual imports (Actions: 3, Columns: 4, Filters: 1)
❌ PropertyResource: 7 individual imports (Actions: 2, Columns: 4, Filters: 1)
❌ BuildingResource: 6 individual imports (Actions: 2, Columns: 3, Filters: 1)
...

RECOMMENDATIONS
================

Priority Resources (5+ individual imports):
  • TariffResource: 8 imports
  • PropertyResource: 7 imports
  • BuildingResource: 6 imports

Estimated Total Effort:
  • Resources to consolidate: 11
  • Estimated time per resource: 30-60 minutes
  • Total estimated time: 8.25 hours
```

**Next steps**:
1. Document findings in tasks.md
2. Create consolidation plan
3. Start with TariffResource (highest priority)

### Example 2: After Consolidation Work

```bash
$ php scripts/count-filament-imports.php

================================================================================
FILAMENT NAMESPACE CONSOLIDATION - IMPORT COUNT REPORT
================================================================================
Generated: 2024-11-29 16:45:30
Total Resources Analyzed: 14
================================================================================

SUMMARY
-------
✅ Already Consolidated: 6 resources
⏭️  Needs Consolidation: 8 resources

RESOURCES NEEDING CONSOLIDATION
-------------------------------
❌ PropertyResource: 7 individual imports (Actions: 2, Columns: 4, Filters: 1)
❌ BuildingResource: 6 individual imports (Actions: 2, Columns: 3, Filters: 1)
...
```

**Progress**:
- Consolidated: 3 → 6 resources (+3)
- Needs work: 11 → 8 resources (-3)
- TariffResource now consolidated ✅

### Example 3: Fully Consolidated Project

```bash
$ php scripts/count-filament-imports.php

================================================================================
FILAMENT NAMESPACE CONSOLIDATION - IMPORT COUNT REPORT
================================================================================
Generated: 2024-12-01 09:15:20
Total Resources Analyzed: 14
================================================================================

SUMMARY
-------
✅ Already Consolidated: 14 resources
⏭️  Needs Consolidation: 0 resources

RECOMMENDATIONS
================

✅ All resources are already consolidated!
   No further action needed.
```

**Achievement unlocked**: Full namespace consolidation! 🎉

## See Also

- [Script API Documentation](COUNT_FILAMENT_IMPORTS_API.md)
- [Namespace Consolidation Tasks](.kiro/specs/6-filament-namespace-consolidation/tasks.md)
- [Consolidation Assessment Guide](../filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md)
