# Organization Single Subscription Relation Design

## Goal

Turn the organization `Subscriptions` relation tab into a single current-subscription management surface:

- if the organization has no subscription, superadmin can create one from the relation tab
- if the organization already has a current subscription, the relation tab shows only that one record
- when one exists, superadmin can edit and manage it, but cannot create a second record from the organization relation tab
- subscription history remains available through the existing history modal and standalone subscriptions resource

## Problem

The current organization relation manager behaves like a subscription history table:

- it lists all subscription rows for the organization
- it has no create action
- it does not expose edit from the relation
- it is inconsistent with the product requirement for the org detail page, where support expects one current subscription item to manage directly

That makes the organization page harder to use for support workflows and allows the relation tab to drift away from the “single current subscription” mental model already used elsewhere in the superadmin control plane.

## Scope

This slice includes:

- changing the organization subscriptions relation query to show only the current subscription
- adding relation-level create when no subscription exists
- adding relation-level edit/manage when a current subscription exists
- hiding relation-level create once one subscription exists
- keeping subscription history visible through the existing history modal
- adding focused Pest coverage for the relation-manager contract

This slice does not include:

- changing the standalone subscriptions resource into a single-record system
- deleting historical subscriptions
- schema changes
- payment, renewal, or plan-change logic rewrites

## Approved Product Decisions

- The organization relation tab should act as a current-subscription manager, not a history browser.
- The relation tab should allow at most one visible subscription row.
- Create is available only when the organization currently has no subscription rows.
- When a subscription exists, the relation tab should expose edit and existing management actions on that one record.
- Historical subscription context stays available through the existing `viewHistory` modal and the standalone subscriptions resource.

## Architecture

The change should stay inside the current Filament structure:

1. `SubscriptionsRelationManager` becomes the orchestration point for relation-tab behavior.
2. The relation query resolves only the organization’s latest/current subscription.
3. Relation header actions mirror existing repo patterns from other relation managers:
   - `CreateAction` for missing current subscription
   - row-level `EditAction` and existing subscription actions for the current record
4. The existing `SubscriptionForm` should be reused where possible, but the parent organization should be fixed by the relation context rather than editable from the relation form.

## Query Impact

Expected query delta:

- relation table result set drops from “all org subscriptions” to at most one row
- create-action visibility uses a lightweight existence check against the parent organization
- history modal behavior remains unchanged

This should reduce relation-tab payload size for organizations with historical subscription rows.

## Testing Strategy

Implement with TDD:

1. Add a failing relation-manager test proving create is available when no subscription exists.
2. Add a failing relation-manager test proving create is hidden when one exists.
3. Add a failing relation-manager test proving only the latest/current subscription is shown when multiple historical rows exist.
4. Add a failing relation-manager test proving edit is available on the current row.
5. Implement the minimal relation-manager changes needed to satisfy the tests.

## Risks and Caveats

- Keep history access available; do not remove the `viewHistory` flow.
- Do not change the global subscriptions resource semantics in this slice.
- Keep the relation form scoped to the parent organization so support cannot accidentally reassign the subscription to another org from this tab.
- Respect the existing superadmin-only subscription policy and action authorizations.
