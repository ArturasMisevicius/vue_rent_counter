# ADR 0007: Feature Flags For Risky Features

## Status

Accepted

## Date

2026-06-15

## Context

Tenanto ships features that can affect billing, tenant access, private files, and platform support. Some changes need staged rollout or emergency disablement.

## Decision

Risky features should have feature flags, organization settings, subscription gates, or release toggles appropriate to the risk. The flag must not replace backend authorization.

## Alternatives Considered

### Big Bang Release

- Pros: no flag management.
- Cons: harder rollback for financial/security changes.
- Rejected for risky modules.

### Flags For Everything

- Pros: maximum control.
- Cons: configuration clutter.
- Rejected as a default.

## Consequences

- PRs must explain whether a feature flag is needed.
- Release checks should call out enabled risky features.
- Tests should cover disabled behavior when a flag protects a workflow.
