# Tenanto Security Threat Model

Date: 2026-03-18  
Scope: authentication routes, unified `/app` panel, CSP and security headers, browser violation reporting, superadmin security visibility

## Evidence Summary

- Security headers were previously minimal in [app/Http/Middleware/SecurityHeaders.php](/Users/andrejprus/Herd/tenanto/app/Http/Middleware/SecurityHeaders.php).
- Auth throttling exists through named rate limiters in [app/Providers/AppServiceProvider.php](/Users/andrejprus/Herd/tenanto/app/Providers/AppServiceProvider.php) and route middleware in [routes/web/guest.php](/Users/andrejprus/Herd/tenanto/routes/web/guest.php).
- The unified panel applies auth and subscription middleware in [app/Providers/Filament/AppPanelProvider.php](/Users/andrejprus/Herd/tenanto/app/Providers/Filament/AppPanelProvider.php).
- Security violations are persisted in [app/Models/SecurityViolation.php](/Users/andrejprus/Herd/tenanto/app/Models/SecurityViolation.php) and shown via [app/Filament/Resources/SecurityViolations/SecurityViolationResource.php](/Users/andrejprus/Herd/tenanto/app/Filament/Resources/SecurityViolations/SecurityViolationResource.php).
- Several layouts render inline `<style>` and `<script>` blocks, including [resources/views/components/shell/app-frame.blade.php](/Users/andrejprus/Herd/tenanto/resources/views/components/shell/app-frame.blade.php), [resources/views/layouts/app.blade.php](/Users/andrejprus/Herd/tenanto/resources/views/layouts/app.blade.php), and [resources/views/components/shell/session-expiry-monitor.blade.php](/Users/andrejprus/Herd/tenanto/resources/views/components/shell/session-expiry-monitor.blade.php).
- Filament also emits inline boot scripts and styles from vendor views, so a fully strict nonce-only CSP would break the live panel without further framework-level overrides.

## Assumptions

- The application is internet-facing for both public auth routes and the authenticated panel.
- Same-origin session authentication is the primary trust boundary.
- Superadmin security visibility must stay read-only and should not leak cross-tenant data to lower roles.

## Prioritized Threats

### 1. Browser injection or asset-tampering impact is higher than current header coverage

Priority: High

Why:
- The app previously shipped without a real CSP header.
- The shell and panel rely on multiple inline script and style surfaces.
- Successful XSS in the authenticated panel would expose session-authenticated tenant and billing workflows.

Chosen mitigation:
- Add a generated CSP nonce per request.
- Apply a compatibility-safe CSP across web and Filament responses.
- Add a report endpoint so blocked resources become observable.

### 2. Credential stuffing and recovery-flow abuse against public auth routes

Priority: High

Why:
- `/login`, `/forgot-password`, and `/reset-password` are public entrypoints.
- Session auth makes brute-force and reset spam a realistic internet-facing attack.

Chosen mitigation:
- Verify named throttles are actually attached to the POST routes.
- Preserve focused regression coverage for the 5-attempt limit.

### 3. Security telemetry blind spots reduce incident response quality

Priority: Medium

Why:
- The `security_violations` table existed, but there was no general intake path for CSP/browser violations.
- URL and user-agent details were not guaranteed to be captured by a shared recorder path.

Chosen mitigation:
- Centralize violation recording in a security monitor service.
- Dispatch a `SecurityViolationDetected` event after persistence.
- Capture user, organization, IP, URL, user agent, severity, type, and raw report metadata.

### 4. HTTPS downgrade risk in production

Priority: Medium

Why:
- Session cookie security depended entirely on environment configuration.
- There was no explicit HTTPS forcing in app boot.

Chosen mitigation:
- Default secure session cookies to production-on.
- Force the URL scheme to HTTPS in production.
- Emit HSTS on secure/production responses.

### 5. Superadmin security visibility gap

Priority: Low to Medium

Why:
- Security violations were available in a dedicated resource, but not surfaced in the broader integration-health operational page.
- That slows triage during platform incident review.

Chosen mitigation:
- Add a recent security violations section to the integration-health page.

## Tradeoff Note

Because Filament 5 currently emits inline scripts and styles from vendor views, the live CSP must keep `'unsafe-inline'` for compatibility. The new nonce and reporting pipeline make future tightening practical, but a strict nonce-only policy would require deeper framework view overrides first.
