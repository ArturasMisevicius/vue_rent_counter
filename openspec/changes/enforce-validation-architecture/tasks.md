## 1. Audit Current Validation Surface
- [ ] 1.1 Inventory all mutating controller actions still using `request()->validate()` or `$request->validate()`.
- [ ] 1.2 Inventory route closures that perform validation inline and map them to controller replacements.
- [ ] 1.3 Inventory Livewire components with inline validation methods, inline rule arrays, or inline message arrays.
- [ ] 1.4 Inventory existing translation-backed validation helpers and current `FormRequest` coverage.

## 2. Controller and Route Validation Normalization
- [ ] 2.1 Create a dedicated `FormRequest` for every mutating controller action that still validates inline.
- [ ] 2.2 Update controller method signatures to type-hint the dedicated `FormRequest` classes.
- [ ] 2.3 Move route-closure validation into controller actions backed by `FormRequest` classes.
- [ ] 2.4 Remove all `request()->validate()` and `$request->validate()` calls from controllers and routes.

## 3. Livewire Validation Normalization
- [ ] 3.1 Create dedicated Livewire form objects or validator/rules classes for each Livewire flow still defining validation inline.
- [ ] 3.2 Update Livewire components to delegate validation to those dedicated classes instead of keeping `rules()` / `messages()` in the component.
- [ ] 3.3 Ensure Livewire validation continues to enforce tenant/property ownership and domain constraints after extraction.

## 4. Translation and Message Reuse
- [ ] 4.1 Standardize translated validation message reuse across `FormRequest` classes, Livewire validation classes, and Filament resources.
- [ ] 4.2 Reuse existing translation helpers where appropriate instead of duplicating message arrays.
- [ ] 4.3 Ensure new validation classes resolve messages from canonical translation keys.

## 5. Tests and Verification
- [ ] 5.1 Add or update Pest tests for controller validation behavior using the new `FormRequest` classes.
- [ ] 5.2 Add or update Livewire tests for extracted form-object or validator-based validation behavior.
- [ ] 5.3 Add regression checks that fail on inline controller validation, inline route validation, and inline Livewire validation definitions.
- [ ] 5.4 Produce a validation-normalization report listing removed inline validation sites, added request/form classes, and any remaining follow-up work.
