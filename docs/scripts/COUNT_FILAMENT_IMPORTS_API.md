# Count Filament Imports Script - API Documentation

## Overview

The `count-filament-imports.php` script is a static analysis tool that examines all Filament resources in the application to assess namespace import patterns and identify consolidation opportunities.

**Location**: `scripts/count-filament-imports.php`

**Purpose**: 
- Quantify namespace consolidation progress
- Identify resources requiring consolidation
- Estimate effort for remaining work
- Track consolidation benefits

## Usage

### Basic Execution

```bash
php scripts/count-filament-imports.php
```

### Output Redirection

```bash
# Save report to file
php scripts/count-filament-imports.php > namespace-report.txt

# View with pagination
php scripts/count-filament-imports.php | less
```

## Analysis Methodology

### Import Pattern Detection

The script uses regex patterns to identify three types of individual imports:

1. **Actions**: `use Filament\Tables\Actions\{ClassName};`
2. **Columns**: `use Filament\Tables\Columns\{ClassName};`
3. **Filters**: `use Filament\Tables\Filters\{ClassName};`

### Consolidation Detection

A resource is considered **consolidated** when:
- Zero individual imports detected
- Consolidated namespace import present: `use Filament\Tables;`

### Status Classification

| Status | Criteria |
|--------|----------|
| `CONSOLIDATED` | No individual imports + has consolidated import |
| `NEEDS CONSOLIDATION` | Has individual imports OR missing consolidated import |

## Report Structure

### 1. Summary Section

```
SUMMARY
-------
✅ Already Consolidated: X resources
⏭️  Needs Consolidation: Y resources
```

**Metrics**:
- Total resources analyzed
- Count of consolidated resources
- Count of resources needing consolidation

### 2. Resources Needing Consolidation

```
RESOURCES NEEDING CONSOLIDATION
-------------------------------
❌ ResourceName: N individual imports (Actions: X, Columns: Y, Filters: Z)
```

**Details**:
- Resource name
- Total individual imports
- Breakdown by import type

### 3. Detailed Breakdown

For each resource:

```
✅/❌ ResourceName
-------------------
File: ResourceName.php
Status: CONSOLIDATED / NEEDS CONSOLIDATION
Individual Imports:
  - Actions:  X
  - Columns:  Y
  - Filters:  Z
  - Total:    N
Consolidated Import: YES ✅ / NO ❌
Table Import: YES / NO
Potential Reduction: P% (N → 1 import)
```

**Metrics**:
- Import counts by type
- Consolidation status
- Table import verification
- Potential code reduction percentage

### 4. Recommendations

```
RECOMMENDATIONS
================

Priority Resources (5+ individual imports):
  • ResourceName: N imports

Estimated Total Effort:
  • Resources to consolidate: X
  • Estimated time per resource: 30-60 minutes
  • Total estimated time: Y hours
```

**Guidance**:
- High-priority resources (5+ imports)
- Effort estimation
- Time allocation recommendations

## Data Structure

### Resource Analysis Object

```php
[
    'file' => 'ResourceName.php',           // Filename
    'actions' => 3,                         // Action import count
    'columns' => 5,                         // Column import count
    'filters' => 2,                         // Filter import count
    'total_individual' => 10,               // Total individual imports
    'has_consolidated' => false,            // Consolidated import present
    'has_table_import' => true,             // Table import present
    'status' => 'NEEDS CONSOLIDATION'       // Consolidation status
]
```

## Calculation Examples

### Potential Reduction Percentage

Formula: `(1 - (1 / (total_individual + 1))) * 100`

Examples:
- 8 imports → 1 import: `(1 - 1/9) * 100 = 88.9%`
- 5 imports → 1 import: `(1 - 1/6) * 100 = 83.3%`
- 3 imports → 1 import: `(1 - 1/4) * 100 = 75.0%`

### Effort Estimation

Formula: `resources_to_consolidate * 0.75 hours`

Example:
- 10 resources × 0.75 hours = 7.5 hours total

## Integration Points

### Related Scripts

- `scripts/verify-batch4-resources.php` - Verifies consolidated resources
- `scripts/verify-tariff-namespace-consolidation.php` - Tariff-specific verification

### Related Documentation

- [.kiro/specs/6-filament-namespace-consolidation/tasks.md](../tasks/tasks.md) - Task tracking
- [docs/filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md](../filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md) - Assessment guide
- [docs/upgrades/FILAMENT_NAMESPACE_CONSOLIDATION.md](../upgrades/FILAMENT_NAMESPACE_CONSOLIDATION.md) - Migration guide

### Memory Bank Integration

Update these files after running the script:

1. **tasks.md**: Update consolidation progress
2. **progress.md**: Record analysis results
3. **activeContext.md**: Note priority resources

## Use Cases

### 1. Initial Assessment

**Scenario**: Starting namespace consolidation project

**Steps**:
1. Run script to establish baseline
2. Identify high-priority resources (5+ imports)
3. Document findings in tasks.md
4. Create consolidation plan

### 2. Progress Tracking

**Scenario**: Monitoring consolidation progress

**Steps**:
1. Run script after each resource consolidation
2. Compare results to previous run
3. Update progress metrics
4. Adjust priorities based on remaining work

### 3. Verification

**Scenario**: Confirming consolidation completion

**Steps**:
1. Run script after consolidation work
2. Verify all resources show "CONSOLIDATED" status
3. Document completion in tasks.md
4. Archive analysis results

### 4. Quality Gate

**Scenario**: Pre-merge verification

**Steps**:
1. Run script in CI/CD pipeline
2. Fail build if new individual imports detected
3. Require consolidation before merge
4. Maintain consolidation standards

## Performance Characteristics

### Execution Time

- **Small projects** (< 10 resources): < 1 second
- **Medium projects** (10-30 resources): 1-2 seconds
- **Large projects** (30+ resources): 2-5 seconds

### Resource Usage

- **Memory**: < 10 MB
- **CPU**: Minimal (single-threaded)
- **I/O**: Read-only file access

## Error Handling

### Common Issues

1. **Resources directory not found**
   - Verify script is run from project root
   - Check `app/Filament/Resources` exists

2. **No resources found**
   - Verify resources exist in directory
   - Check file naming convention (*Resource.php)

3. **Permission errors**
   - Ensure read access to resources directory
   - Check file permissions

## Extending the Script

### Adding New Import Types

```php
// Add new import pattern
$formImports = preg_match_all('/^use Filament\\\\Forms\\\\Components\\\\[^;]+;/m', $content);

// Update total calculation
$totalIndividual = $actionImports + $columnImports + $filterImports + $formImports;

// Update output
echo "  - Forms:    {$data['forms']}\n";
```

### Custom Reporting

```php
// Add JSON output option
if (isset($argv[1]) && $argv[1] === '--json') {
    echo json_encode($resources, JSON_PRETTY_PRINT);
    exit;
}
```

### Filtering Results

```php
// Show only resources needing consolidation
if (isset($argv[1]) && $argv[1] === '--needs-work') {
    $resources = array_filter($resources, fn($r) => $r['status'] === 'NEEDS CONSOLIDATION');
}
```

## Best Practices

### Regular Execution

- Run after each resource consolidation
- Include in code review checklist
- Schedule periodic audits

### Documentation Updates

- Update tasks.md with results
- Track progress in progress.md
- Document decisions in activeContext.md

### Team Communication

- Share reports with team
- Discuss priority resources
- Coordinate consolidation efforts

## Troubleshooting

### Issue: Incorrect Import Counts

**Cause**: Regex pattern not matching import format

**Solution**: Verify import statement format matches pattern

### Issue: False Positives

**Cause**: Commented imports counted

**Solution**: Script counts all matching patterns; manually verify

### Issue: Missing Resources

**Cause**: Resources in subdirectories not scanned

**Solution**: Update glob pattern to include subdirectories

## Version History

- **1.0.0** (2024-11-29): Initial implementation
  - Basic import counting
  - Consolidation status detection
  - Report generation
  - Effort estimation

## Related Commands

```bash
# Run verification script
php scripts/verify-batch4-resources.php

# Check code style
./vendor/bin/pint scripts/count-filament-imports.php

# Run static analysis
./vendor/bin/phpstan analyse scripts/count-filament-imports.php
```

## See Also

- [Namespace Consolidation Tasks](../tasks/tasks.md)
- [Consolidation Assessment Guide](../filament/NAMESPACE_CONSOLIDATION_ASSESSMENT.md)
- [Filament 4 Migration Guide](../upgrades/FILAMENT_V4_MIGRATION.md)
