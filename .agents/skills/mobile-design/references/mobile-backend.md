# Mobile Backend

## Core expectations
- Design APIs for intermittent connectivity and retries.
- Use pagination and filtering to minimize payloads.
- Prefer idempotent writes where possible.
- Add server-side validation and clear error codes.

## Offline and sync
- Cache essential data locally for offline mode.
- Use background sync with exponential backoff.
- Resolve conflicts deterministically and visibly.

## Push notifications
- Treat device tokens as rotating secrets.
- Support token refresh and unsubscribe flows.
- Never include sensitive data in push payloads.

## Performance
- Compress responses where appropriate.
- Avoid large images; provide thumbnails and lazy loading.
- Keep round trips low; batch when safe.
