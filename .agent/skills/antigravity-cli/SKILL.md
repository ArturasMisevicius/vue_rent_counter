---
name: antigravity-cli
description: Use when working with the Antigravity CLI (ag-kit) to install, update, or check the status of Antigravity Kit files in a project. Triggers include requests about running ag-kit, managing the .agent directory, or automating Antigravity Kit updates in scripts or CI.
---

# Antigravity Cli

## Overview

Use this skill to operate the Antigravity CLI (ag-kit) in a repo: initialize the kit, update the .agent directory, and verify status. Favor safe, repeatable commands and confirm intent before overwriting local changes.

## Quick Start

- Confirm the tool: Antigravity Kit (ag-kit) vs other similarly named tools. Ask for clarification if the user mentions unrelated features.
- Prefer `npx @vudovn/ag-kit <command>` for one-off runs; use a global install only if requested.
- Run from the project root, or pass `--path` to target a specific folder.
- Use `--dry-run` to preview changes when in doubt.

## Tasks

### Initialize the kit

- Use `init` to create `.agent/` in the target directory.
- If `.agent/` already exists, `init` skips unless `--force` is used.
- Use `--path` for a non-current directory.
- Avoid `--force` unless the user explicitly wants to overwrite existing files.

### Update the kit

- Use `update` to pull the latest kit into `.agent/`.
- Recommend backing up any local changes to `.agent/` before updating.
- Use `--dry-run` to show planned changes without writing.

### Check status

- Use `status` to confirm whether `.agent/` is up to date.
- If it reports drift, propose `update` (or `update --dry-run`) and explain the impact.

## Safety and Guardrails

- Never overwrite `.agent/` without explicit user confirmation.
- If the user has customized `.agent/`, suggest backing it up or committing before `update --force`.
- In CI or automation, prefer `--dry-run` first and fail fast on unexpected diffs.

## Upgrade Playbook

- Snapshot: commit or back up `.agent/` before any update.
- Inspect: run `status` and/or `update --dry-run` to see planned changes.
- Update: run `update` (avoid `--force` unless explicitly requested).
- Verify: run `status` and confirm expected counts (agents/skills/workflows).
- Rollback: restore the backup or revert the commit if the update caused issues.

## CI Recipes

Use these as starting points; keep commands minimal and deterministic.

### GitHub Actions

```yaml
name: Antigravity Kit
on: [push, pull_request]
jobs:
  antigravity:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 18
      - run: npx @vudovn/ag-kit status
```

### GitLab CI

```yaml
antigravity:
  image: node:18
  script:
    - npx @vudovn/ag-kit status
```

### Azure Pipelines

```yaml
steps:
  - task: NodeTool@0
    inputs:
      versionSpec: "18.x"
  - script: npx @vudovn/ag-kit status
    displayName: Antigravity Kit status
```

## Resources

### references/
Reference docs for commands and flags: `references/cli_reference.md`.
