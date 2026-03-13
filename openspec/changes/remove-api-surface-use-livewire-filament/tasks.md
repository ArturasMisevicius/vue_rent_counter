## 1. API Surface Removal
- [x] 1.1 Stop loading API routes from bootstrap.
- [x] 1.2 Remove API route files and API controllers.
- [x] 1.3 Remove residual direct `/api/*` client calls from UI code.

## 2. Livewire/Filament Web Migration
- [x] 2.1 Implement manager meter-reading form as a Livewire component.
- [x] 2.2 Replace API-backed create page integration with Livewire component rendering.
- [x] 2.3 Preserve validation, monotonic checks, anomaly warnings, and tariff-based charge preview.

## 3. Test and Validation
- [x] 3.1 Update/create feature tests for Livewire meter-reading form behavior.
- [x] 3.2 Add a regression check that `/api/*` endpoints are no longer available.
- [x] 3.3 Run formatting and targeted tests.
