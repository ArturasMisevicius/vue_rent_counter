# Agent Documentation Reader

## Core Principle

The agent should read ONLY the relevant steering files needed for the specific task at hand, rather than all documentation upfront. This ensures efficiency while maintaining consistency with project standards.

## Task-Based Documentation Loading

### Intelligent Steering File Selection

The agent uses a task-to-steering mapping system (`.kiro/system/task-steering-mapping.json`) to determine which steering files are relevant for each task category:

#### Task Categories & Required Steering Files

**Authentication Tasks:**
- `filament-auth-tenancy.md` - Auth patterns and tenancy
- `filament-shield.md` - Role-based access control
- `laravel.md` - Laravel auth conventions
- `ILO-LARAVEL12.md` - Laravel 12 auth patterns
- `testing-standards.md` - Auth testing requirements

**Filament Development:**
- `filament-conventions.md` - Core Filament patterns
- `filament-forms-inputs.md` - Form development
- `filament-navigation.md` - Navigation patterns
- `filament-dashboard-widgets.md` - Widget development
- `filament-performance.md` - Performance optimization

**Testing Tasks:**
- `ILO-TESTING.md` - Testing requirements
- `testing-standards.md` - Testing patterns
- `pest-route-testing.md` - Route testing
- `filament-testing.md` - Filament-specific testing

**Laravel Development:**
- `ILO-LARAVEL12.md` - Laravel 12 patterns
- `laravel-architecture-patterns.md` - Architecture patterns
- `laravel-container-services.md` - Service patterns
- `blade-guardrails.md` - Blade best practices

### Common Files (Always Loaded)
- `goals.md` - Project objectives and success metrics
- `product.md` - Product vision and requirements
- `operating-principles.md` - Core development principles

## Pre-Task Documentation Review

Before starting any development task, the agent should:

1. **Identify Task Category** - Determine the primary task type (authentication, filament, testing, etc.)
2. **Load Relevant Steering Files** - Read only the steering files mapped to that task category
3. **Load Common Files** - Always read the common steering files for context
4. **Skip Irrelevant Documentation** - Do not load steering files unrelated to the current task

## Task-Specific Documentation Loading Process

### Step 1: Task Analysis
```
1. Examine the task description or spec file
2. Identify primary task category (authentication, filament, testing, etc.)
3. Check for secondary categories if task spans multiple areas
4. Look up required steering files in task-steering-mapping.json
```

### Step 2: Selective Loading
```
1. Load common steering files (goals.md, product.md, operating-principles.md)
2. Load category-specific steering files
3. Skip all other steering files to maintain focus
4. Reference additional files only if specifically needed during development
```

### Step 3: Context Building
```
1. Understand project goals and constraints from common files
2. Apply category-specific patterns and conventions
3. Maintain consistency with loaded steering guidelines
4. Proceed with task implementation
```

## Spec-to-Category Mapping

The system automatically maps spec directories to task categories:

- `authentication-testing/` → authentication, testing, filament
- `universal-utility-management/` → utilities, laravel, filament, database
- `hierarchical-scope-enhancement/` → laravel, architecture, security
- `superadmin-dashboard-enhancement/` → filament, utilities, frontend
- `design-system-integration/` → frontend, filament, architecture

## Dynamic Loading Rules

### When to Load Additional Files
- **Cross-cutting concerns** - Load security files for any task involving user data
- **Integration tasks** - Load multiple category files for complex features
- **Refactoring tasks** - Load architecture and quality files
- **Bug fixes** - Load testing and quality files

### When to Skip Files
- **Focused tasks** - Skip unrelated categories entirely
- **Quick fixes** - Load only essential files for small changes
- **Maintenance tasks** - Load only quality and testing files

## Efficiency Benefits

1. **Faster Task Startup** - Read only 3-7 files instead of 30+ files
2. **Better Focus** - Avoid information overload from irrelevant guidelines
3. **Reduced Cognitive Load** - Process only relevant patterns and conventions
4. **Maintained Quality** - Still ensure consistency with applicable standards

## Documentation Maintenance

### Steering File Updates
- Update steering files when patterns change
- Keep task-steering-mapping.json current with new categories
- Add new spec mappings when creating new features
- Remove obsolete mappings when deprecating features

### Mapping System Maintenance
- Review mappings quarterly for accuracy
- Add new task categories as project evolves
- Update category descriptions for clarity
- Ensure common files remain universally applicable

## Windows Integration with Kiro IDE

### Python Script Usage (Recommended)
```cmd
REM Show steering files for a task category
python .kiro\scripts\get_steering_files.py authentication

REM Show steering files for a spec
python .kiro\scripts\get_steering_files.py universal-utility-management

REM List files only (for programmatic use)
python .kiro\scripts\get_steering_files.py filament --list

REM Show all available categories and mappings
python .kiro\scripts\get_steering_files.py --all
```

### PowerShell Script Usage (Alternative)
```powershell
# Show steering files for a task category
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "authentication"

# Show steering files for a spec
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "universal-utility-management"

# List files only (for programmatic use)
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "filament" -ListOnly

# Show all available categories and mappings
.\\.kiro\scripts\Get-SteeringFiles.ps1 -ShowAll
```

### Kiro IDE Integration
The system integrates with Kiro IDE through:
- **Task Detection**: Automatically identifies task categories from spec directories
- **File Loading**: Loads only relevant steering files for the current task
- **Windows Compatibility**: Uses PowerShell and batch scripts for Windows environments
- **Configuration**: Uses `.kiro/system/steering-loader.json` for IDE integration

## Usage Instructions

1. **Task Start** - Run steering file script to identify required files
2. **Load Context** - Read only the identified steering files
3. **During Development** - Reference loaded steering files for patterns
4. **Need Additional Context** - Use script to load additional categories if needed
5. **Task Completion** - Update relevant steering files if patterns changed

### Example Workflow
```powershell
# 1. Identify required files for utility management
.\\.kiro\scripts\Get-SteeringFiles.ps1 -TaskInput "universal-utility-management"

# 2. Load the identified files (shown in script output)
# 3. Proceed with development using loaded context
# 4. Update steering files if new patterns emerge
```

This Windows-compatible approach ensures efficient task execution while maintaining project consistency and quality standards.