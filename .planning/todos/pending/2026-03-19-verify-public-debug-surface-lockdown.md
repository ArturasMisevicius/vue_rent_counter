---
created: 2026-03-19T14:37:30.378Z
title: Verify public debug surface lockdown
area: general
files:
  - public/index.php
  - routes/web.php
  - lang/en
  - tests/Feature/Security/NoPublicDebugFilesTest.php
  - .mcp.json
---

## Problem

The requested security cleanup targets publicly reachable debug PHP files, a raw `/test-debug` route, and development-only translation artifacts. In the live repository snapshot, those risky files and route already appear to be removed, and `tests/Feature/Security/NoPublicDebugFilesTest.php` already exists as a regression guard. The remaining work is to verify that the cleanup is truly complete, confirm the translation catalog is still healthy, and document that the requested `laravel-boost` and `laravel-mcp` servers are not currently configured in `.mcp.json`.

## Solution

Treat this task as a lockdown verification pass:

1. Confirm the public web root exposes only `index.php` as a PHP entrypoint.
2. Confirm `/test-debug` is absent from the route graph and still returns `404`.
3. Confirm the listed translation artifact files are not present under `lang/en`.
4. Run the existing security feature tests and translation check command to validate the regression coverage.
5. Review the existing Pest assertions with a security-audit lens before finalizing the session.
