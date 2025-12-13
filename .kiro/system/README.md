# Kiro Steering File Management System

## Overview

This system provides intelligent, task-focused loading of steering files for Kiro IDE on Windows. Instead of loading all 30+ steering files, it loads only the 3-10 files relevant to your current task, improving efficiency by 60-80%.

## Files Structure

```
.kiro/system/
├── task-steering-mapping.json    # Core mapping configuration
├── steering-loader.json          # Kiro IDE integration config
└── README.md                     # This file

.kiro/scripts/
├── get_steering_files.py         # Python script (recommended)
├── Get-SteeringFiles.ps1         # PowerShell script
└── Get-SteeringFiles.bat         # Batch wrapper
```

## Quick Start

### Using Python (Recommended)
```cmd
# Show steering files for utility management
python .kiro\scripts\get_steering_files.py universal-utility-management

# Show steering files for authentication tasks
python .kiro\scripts\get_steering_files.py authentication

# List all available categories
python .kiro\scripts\get_steering_files.py --all
```

### Using PowerShell
```powershell
# Show steering files for a task
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "filament"

# List files only (for automation)
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "testing" -ListOnly
```

### Using Batch (Windows CMD)
```cmd
# Show steering files
.kiro\scripts\Get-SteeringFiles.bat authentication

# Show all categories
.kiro\scripts\Get-SteeringFiles.bat --all
```

## Task Categories

The system recognizes these task categories:

### Core Development
- **authentication** - Auth, tenancy, and security tasks
- **filament** - Filament admin panel development
- **laravel** - Laravel framework development
- **frontend** - Blade, Alpine.js, Tailwind CSS

### Specialized Areas
- **testing** - Testing, QA, and code coverage
- **utilities** - Utility management and billing
- **database** - Database design and migrations
- **architecture** - System design and patterns
- **quality** - Code quality and static analysis
- **translation** - Multi-language support
- **security** - Security hardening
- **performance** - Performance optimization

## Spec Mappings

Spec directories automatically map to categories:

- `universal-utility-management/` → utilities, laravel, filament, database
- `authentication-testing/` → authentication, testing, filament
- `superadmin-dashboard-enhancement/` → filament, utilities, frontend
- `hierarchical-scope-enhancement/` → laravel, architecture, security

## Example Output

For `universal-utility-management`:

```
🎯 Target Steering Files for: universal-utility-management
==================================================

📂 Categories: utilities, laravel, filament, database
📝 Description: Multi-category spec: utilities, laravel, filament, database

📋 Common Files (always load):
  ✓ goals.md
  ✓ product.md
  ✓ operating-principles.md

🎯 Task-Specific Files:
  ✓ ILO-LARAVEL12.md
  ✓ blade-guardrails.md
  ✓ filament-conventions.md
  ✓ laravel-architecture-patterns.md
  ✓ structure.md
  ✓ translation-guide.md
  ... (15 more files)

📊 Summary:
  Total files to load: 24
  Files skipped: 50
  Efficiency gain: 67.6%
```

## Configuration

### Adding New Task Categories

Edit `.kiro/system/task-steering-mapping.json`:

```json
{
  "task_categories": {
    "new_category": {
      "steering_files": [
        "relevant-file1.md",
        "relevant-file2.md"
      ],
      "description": "Description of this task category"
    }
  }
}
```

### Adding Spec Mappings

```json
{
  "spec_mappings": {
    "new-spec-directory": ["category1", "category2"]
  }
}
```

### Updating Common Files

```json
{
  "common_files": [
    "goals.md",
    "product.md",
    "operating-principles.md"
  ]
}
```

## Integration with Kiro IDE

The system integrates with Kiro IDE through:

1. **Automatic Detection** - IDE detects task type from spec directories
2. **File Loading** - Loads only relevant steering files
3. **Windows Compatibility** - Works with PowerShell, Python, and batch scripts
4. **Configuration** - Uses `steering-loader.json` for IDE settings

## Troubleshooting

### Python Script Issues
```cmd
# Check Python installation
python --version

# Run with full path if needed
python C:\path\to\project\.kiro\scripts\get_steering_files.py --all
```

### PowerShell Execution Policy
```powershell
# Check current policy
Get-ExecutionPolicy

# Set policy for current user (if needed)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Missing Files
If files show as `❌ MISSING`, check:
1. File exists in `.kiro/steering/`
2. Filename matches exactly (case-sensitive)
3. File has `.md` extension

## Benefits

- **67% Efficiency Gain** - Load 24 files instead of 74
- **Faster Startup** - Reduced context loading time
- **Better Focus** - Only relevant guidelines loaded
- **Maintained Quality** - Still ensures consistency
- **Windows Compatible** - Works with all Windows environments

## Maintenance

### Regular Tasks
1. **Review mappings quarterly** - Ensure categories are accurate
2. **Update spec mappings** - Add new specs as they're created
3. **Validate file existence** - Ensure all referenced files exist
4. **Update descriptions** - Keep category descriptions current

### When Adding New Features
1. Determine primary task category
2. Add spec mapping if creating new spec directory
3. Update category files if new patterns emerge
4. Test with steering file scripts

## Support

For issues or questions:
1. Check this README
2. Run `python .kiro\scripts\get_steering_files.py --all` to see all options
3. Verify file paths and permissions
4. Check Kiro IDE documentation for integration details