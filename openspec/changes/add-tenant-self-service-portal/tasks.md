# Tasks: Add Tenant Self-Service Portal

Source plan:
`docs/superpowers/plans/2026-03-17-tenant-self-service-portal.md`

## 1. Prepare The Portal Shell

- [ ] Confirm the prerequisite admin-domain models, policies, and shared
      reading/invoice services exist or are being delivered on the same branch
- [ ] Add tenant route skeletons for home, readings, invoices, property, and
      profile inside the authenticated locale-aware route group
- [ ] Replace the placeholder tenant home shell with a shared tenant portal
      layout and fixed bottom navigation
- [ ] Add localized tenant copy files for supported languages
- [ ] Add feature coverage for tenant navigation and non-tenant denial

## 2. Deliver Home And Property Surfaces

- [ ] Add tenant portal test fixtures/factories for property, meter, reading,
      and invoice states
- [ ] Implement `TenantHomePresenter`, `PaymentInstructionsResolver`, and the
      `HomeSummary` Livewire component
- [ ] Expand tenant home with outstanding balance, recent readings, month
      summary, property entry point, and paid-up/empty states
- [ ] Implement `TenantPropertyPresenter` and the read-only `My Property` page
- [ ] Add feature coverage for home and property scenarios

## 3. Deliver Reading Submission

- [ ] Add tenant reading submission feature and Livewire tests
- [ ] Implement `SubmitTenantReadingAction` and `SubmitReadingPage`
- [ ] Reuse the shared reading validation and creation path from the admin
      domain
- [ ] Add live consumption preview and full success confirmation state

## 4. Deliver Invoice History

- [ ] Add invoice history feature coverage for filters, paid/unpaid states, and
      download authorization
- [ ] Implement `TenantInvoiceIndexQuery` and the invoice history page
- [ ] Add secure tenant PDF download using the shared invoice document path

## 5. Deliver Profile Management

- [ ] Add profile and password update feature coverage, including locale
      switching behavior
- [ ] Implement tenant profile/password Form Requests, Actions, and controllers
- [ ] Keep locale persistence routed through `UpdateUserLocaleAction`

## 6. Finalize Isolation And Verification

- [ ] Add tenant access-isolation tests for property access, invoice downloads,
      and reading submission boundaries
- [ ] Extend the existing auth isolation suite with a portal-level regression
- [ ] Run the tenant feature suite, auth isolation regression, and `pint`
- [ ] Prepare the change for implementation handoff or execution
