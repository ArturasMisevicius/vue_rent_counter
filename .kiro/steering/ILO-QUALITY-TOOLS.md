---
inclusion: always
---

# ILO CODE – Quality Tools Config
- PHPStan level 9 + treatPhpDocTypesAsCertain = false
- Larastan + strict rules
- Rector running on CI with --dry-run false
- Laravel Pint with custom kilo preset (no line longer than 100, strict PSR-12)
- Deptrac for architecture enforcement (Domains cannot use Filament directly)
- Git hooks: pint + phpstan + pest --coverage