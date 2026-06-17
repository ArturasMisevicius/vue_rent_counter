# Migration Guidelines

> **AI agent usage:** Use this before adding or changing migrations, especially for financial, tenant, document, KYC, or access-control tables.

Updated on 2026-06-15.

## Rule

Migrations must be safe to deploy and rollback. Financial and access-control changes need extra caution.

## Additive First

Prefer:

1. add nullable column or new table;
2. backfill through a command/job when needed;
3. switch application code;
4. enforce non-null or remove legacy fields in a later migration.

## Indexes

Add indexes for:

- foreign keys;
- organization scopes;
- tenant scopes;
- status filters;
- date/order columns used in dashboards and commands.

## Destructive Changes

Do not drop columns, rewrite financial state, or perform large data changes without an ADR or release plan.

## Data Migrations

Large data migrations should be commands or jobs, not long-running schema migrations. They need progress output, chunking, and a rollback/repair story.

## PR Requirements

Migration PRs must state:

- backward compatibility;
- downtime risk;
- backfill plan;
- rollback plan;
- data quality checks.
