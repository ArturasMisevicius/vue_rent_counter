---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**"
---

# Filament Notifications

## When to notify
- Success on create/update/delete only when user-triggered; stay quiet for background autosaves.
- Warnings/errors for validation or permission issues; give actionable text.
- Use notifications sparingly for bulk actions and imports; avoid stacking noise.

## Content & tone
- Title: short verb phrase; Body: concise detail; Avoid shouting/caps.
- Include helpful links/buttons only when they resolve next steps.

## Actions
- Use notification actions for approve/reject/view flows; ensure idempotent callbacks.
- For destructive follow-ups, require confirmation in the action.
- When background work finishes (imports, schedulers), wrap the Filament notification with `RealTimeFilamentNotification` so users get the toast via broadcast as well as in-panel; keep channels tenant/user scoped.

## Colors/icons
- Success = primary/success icon; Warning for recoverable issues; Danger for destructive/failure.
- Keep icons aligned with Filament defaults; avoid mixing outline/solid styles in the same app area.

## Throttling
- Collapse duplicate notifications in loops; debounce repeat success to once per operation.

## Testing
- Assert notifications on key actions; verify content and type; ensure unauthorized paths don’t notify success.
