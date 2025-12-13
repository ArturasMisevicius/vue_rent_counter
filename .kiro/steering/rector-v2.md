---
inclusion: always
---

# Rector v2 Automated Refactoring

## Core Principles
- Rector v2 automates PHP refactoring, Laravel upgrades, and code quality improvements using composer-based detection.
- Always run `composer lint` before commits to apply Rector fixes followed by Pint formatting.
- Use `composer test:refactor` (dry-run) in CI to verify no pending refactors exist.
- Review Rector changes before committing; don't blindly accept all refactoring suggestions.

## Configuration (`rector.php`)
- Uses `RectorConfig::configure()` with `LaravelSetProvider` for automatic Laravel version detection.
- Enabled sets: `LARAVEL_CODE_QUALITY`, `LARAVEL_COLLECTION`, `LARAVEL_TESTING`, `LARAVEL_TYPE_DECLARATIONS`.
- Prepared sets: `deadCode`, `codeQuality`, `typeDeclarations`, `privatization`, `earlyReturn`.
- Processes: `app/`, `app-modules/`, `bootstrap/app.php`, `config/`, `database/`, `lang/`, `routes/`, `tests/`.
- Automatically removes debug helpers: `dd`, `ddd`, `dump`, `ray`, `var_dump`.

## Skip Rules
- Filament importer lifecycle hooks (`app/Filament/Imports/*`) skip `RemoveUnusedPrivateMethodRector` and `PrivatizeFinalClassMethodRector` since methods are called dynamically.
- `AppServiceProvider` skips first-class callable conversion due to `class_exists()` parameter conflicts.
- `AddOverrideAttributeToOverriddenMethodsRector` skipped globally until PHP 8.3+ is minimum.

## Common Refactoring Patterns
- **Array to Collection**: Converts `array_map()` to `collect()->map()`.
- **Type Declarations**: Adds return types and parameter types automatically.
- **Early Returns**: Converts nested conditions to early return patterns.
- **Dead Code**: Removes unused private methods, variables, and unreachable code.
- **Laravel Helpers**: Modernizes array helpers, request handling, and validation patterns.

## Usage Patterns
```bash
# Apply fixes (write mode)
composer lint

# Preview changes (dry-run)
composer test:refactor

# Process specific path
vendor/bin/rector process app/Services/NewFeature

# Clear cache
vendor/bin/rector clear-cache
```

## Integration with Workflow
- **Pre-commit**: Run `composer lint` to apply Rector + Pint.
- **CI/CD**: Run `composer test:refactor` to verify no pending refactors.
- **Full test suite**: `composer test` includes Rector dry-run check.
- **After refactoring**: Always run `composer test` to verify tests pass.

## Extending Rector
- Add custom rules in `app/Rector/` and register in `rector.php` with `->withRules()`.
- Add more Laravel sets from `LaravelSetList` as needed (e.g., `LARAVEL_FACADE_ALIASES_TO_FULL_NAMES`).
- Add project-specific debug helpers to `RemoveDumpDataDeadCodeRector` configuration.
- Use `->withSkip()` to exclude specific rules or paths that cause false positives.

## Best Practices
- ✅ Run Rector before every commit via `composer lint`.
- ✅ Review changes in git diff before committing.
- ✅ Use `--dry-run` to preview changes first.
- ✅ Clear cache after configuration changes.
- ✅ Test thoroughly after Rector refactoring.
- ✅ Skip rules that cause false positives with inline `@noRector` comments.
- ❌ Don't blindly accept all Rector changes.
- ❌ Don't skip Rector in CI pipeline.
- ❌ Don't disable rules without understanding why.
- ❌ Don't commit without running `composer lint`.

## Performance
- Rector v2 uses parallel processing automatically.
- Cache stored in `var/cache/rector/` for faster subsequent runs.
- Process specific directories when working on features to speed up feedback.
- Increase memory limit if needed: `php -d memory_limit=1G vendor/bin/rector`.

## Documentation
- Comprehensive guide: `docs/rector-v2-integration.md`
- Laravel-specific rules: `docs/rector-laravel.md`
- Available sets: [Rector Laravel Rules Overview](https://github.com/driftingly/rector-laravel/blob/main/docs/rector_rules_overview.md)
