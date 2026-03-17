# OpenSpec Working Guide

## Purpose

This directory holds proposal-time and source-of-truth specs for Tenanto.
Use it when work introduces a new capability, changes existing behavior, or
needs a reviewable implementation proposal before coding.

## Directory Layout

- `openspec/AGENTS.md`: local OpenSpec workflow for this repository
- `openspec/specs/<capability>/spec.md`: current approved capability specs
- `openspec/changes/<change-id>/proposal.md`: why the change exists
- `openspec/changes/<change-id>/design.md`: architecture and tradeoffs for larger changes
- `openspec/changes/<change-id>/tasks.md`: implementation checklist
- `openspec/changes/<change-id>/specs/<capability>/spec.md`: spec deltas for the proposed change

## Authoring Rules

1. Use short verb-led kebab-case change ids such as `add-tenant-self-service-portal`.
2. Keep specs capability-focused. Split unrelated behavior into separate capability folders.
3. Write proposal/design/tasks for the implementation plan and keep requirements user-visible.
4. In spec deltas, use `## ADDED Requirements`, `## MODIFIED Requirements`, or
   `## REMOVED Requirements`.
5. Every requirement statement must use `SHALL`.
6. Every requirement should include at least one `#### Scenario:` block written with
   `GIVEN`, `WHEN`, and `THEN`.
7. Put Laravel implementation details in `design.md` and `tasks.md`, not in the requirement sentence itself.

## Project-Specific Constraints

- The root [`AGENTS.md`](/Users/andrejprus/Herd/tenanto/AGENTS.md) remains authoritative.
- Specs must preserve the Laravel and Eloquent rules from the root instructions:
  no raw SQL, no Blade queries, no duplicate business logic, and tenant-safe authorization.
- When a detailed implementation plan already exists under `docs/superpowers/plans/`,
  mirror its chunking in `tasks.md` and capture the durable product behavior in the spec deltas.
- For tenant features, prefer shared domain models, policies, and actions over tenant-only duplicates.

## Review Checklist

- Does the proposal describe the current gap and the user-facing outcome?
- Do the tasks map cleanly to the implementation plan?
- Do the spec deltas define externally observable behavior instead of code structure?
- Are multi-tenant boundaries and authorization requirements explicit?
