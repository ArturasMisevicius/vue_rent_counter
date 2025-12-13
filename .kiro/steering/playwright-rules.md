---
inclusion: always
---

# Playwright Testing Rules

## Integration Guidelines
- Use `hyvor/laravel-playwright` features (e.g., `php()`, `artisan()`) to manipulate state efficiently.
- Avoid UI-based login; use `actAs($user)` or similar helpers if available, or fast login flows.
- Keep tests independent. Use `DatabaseTransactions` or explicitly clean up if persistence is needed.

## Location
- All E2E tests must be in `tests/Playwright/`.
- Helper functions should be in `tests/Playwright/Support/`.

## Best Practices
- Prefer data-testid attributes or semantic locators over generic CSS selectors.
- Don't sleep/hard wait. Use `expect().toBeVisible()` or `await page.waitFor...`.
- Use the `test:e2e` script to run tests.
