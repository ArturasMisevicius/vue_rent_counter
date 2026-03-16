## ADDED Requirements
### Requirement: Dedicated Form Requests for Mutating Controllers
The system SHALL validate every mutating HTTP controller action through a dedicated `FormRequest` class.

#### Scenario: Mutating controller action validates through a request class
- **WHEN** a controller action creates, updates, deletes, or performs another mutating operation
- **THEN** the action receives a dedicated `FormRequest`
- **AND** the controller does not call `request()->validate()` or `$request->validate()`

### Requirement: No Inline Route Validation
The system SHALL not perform inline validation inside route closures.

#### Scenario: Validated route endpoint uses a controller
- **WHEN** a web route requires validated input for a mutating operation
- **THEN** the route resolves to a controller action
- **AND** validation is handled by a dedicated `FormRequest`

### Requirement: Extracted Livewire Validation
The system SHALL keep validation rules and messages out of Livewire component classes.

#### Scenario: Livewire business form delegates validation
- **WHEN** a Livewire component handles a business form submission
- **THEN** validation is performed by a dedicated Livewire form object or validator/rules class
- **AND** the component class does not define inline `rules()` or `messages()` for that form flow

### Requirement: Canonical Translated Validation Messages
The system SHALL reuse translated validation messages consistently across controller requests, Livewire validation, and Filament forms.

#### Scenario: Validation messages come from canonical translations
- **WHEN** validation fails on a controller action, Livewire action, or Filament form
- **THEN** the error messages are resolved from canonical translation keys
- **AND** duplicate inline message arrays are not introduced where shared translated messages already exist

### Requirement: Regression Protection Against Inline Validation
The system SHALL include automated tests or checks that fail if inline validation is reintroduced in forbidden surfaces.

#### Scenario: Forbidden validation pattern is detected
- **WHEN** inline validation is added back to a controller, route closure, or Livewire component class
- **THEN** the regression checks fail and block the change
