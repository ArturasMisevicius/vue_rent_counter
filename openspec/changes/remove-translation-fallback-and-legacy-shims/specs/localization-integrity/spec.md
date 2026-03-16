## ADDED Requirements
### Requirement: Canonical Enabled Locale Set
The system SHALL treat the locale list declared in `lang/locales.php` as the source of truth for supported application locales.

#### Scenario: Enabled locales are aligned with repository support
- **WHEN** the application or test suite enumerates supported locales
- **THEN** it uses the locales declared in `lang/locales.php`
- **AND** every enabled locale has the canonical translation coverage required by the application

#### Scenario: Partial locale artifacts are not left enabled by accident
- **WHEN** a locale is present on disk but is not approved as part of the canonical locale set
- **THEN** it is removed or otherwise excluded from supported application locales

### Requirement: Canonical Translation Tree
The system SHALL use `lang/` as the only canonical translation tree for application translations.

#### Scenario: Application translations do not live in resources/lang
- **WHEN** translation files are loaded for the application
- **THEN** they resolve from `lang/`
- **AND** application translation files are not maintained under `resources/lang`

### Requirement: Direct Canonical Translation Resolution
The system SHALL resolve canonical translation keys directly instead of relying on runtime missing-key remapping.

#### Scenario: Canonical keys resolve without translator fallback bridging
- **WHEN** a page, component, Livewire module, request, or Filament surface resolves a translation key
- **THEN** it uses a canonical key that exists in `lang/`
- **AND** the application does not rely on `handleMissingKeysUsing(...)` to bridge legacy keys at runtime

#### Scenario: Missing canonical keys fail verification
- **WHEN** an enabled locale is missing a required canonical translation key
- **THEN** automated tests fail instead of silently masking the issue through a fallback bridge

### Requirement: Filament 5 API Usage Without Alias Shims
The system SHALL use current Filament 5 classes directly and SHALL not depend on bootstrap alias shims for legacy Filament class names.

#### Scenario: Filament surfaces import current classes directly
- **WHEN** Filament resources, widgets, pages, relation managers, or related tests are loaded
- **THEN** they import the current Filament 5 classes directly
- **AND** application bootstrap and test bootstrap do not register legacy Filament `class_alias` shims

### Requirement: Legacy Compatibility Cleanup Guardrails
The system SHALL remove dead compatibility files and routes after migration verification and SHALL guard against their reintroduction.

#### Scenario: Dead compatibility files and routes are removed
- **WHEN** a compatibility file or route exists only to support superseded translation or UI behavior
- **THEN** it is deleted after its callers are migrated

#### Scenario: Regression checks block legacy pattern reintroduction
- **WHEN** a forbidden legacy pattern such as `resources/lang` application files, runtime translation fallback bridges, or Filament alias shims is reintroduced
- **THEN** automated verification fails
