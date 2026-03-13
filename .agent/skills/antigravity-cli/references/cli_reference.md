# Antigravity CLI Reference

## Overview

Antigravity CLI (ag-kit) installs, updates, and checks the status of Antigravity Kit files in a project.

## Install / Run

- One-off via npx:
  - `npx @vudovn/ag-kit init`
  - `npx @vudovn/ag-kit update`
  - `npx @vudovn/ag-kit status`
- Global install:
  - `npm install -g @vudovn/ag-kit`
  - `ag-kit init`

## Commands

### init
Initialize Antigravity Kit in the current directory (creates `.agent/`).
If `.agent/` already exists, `init` skips unless `--force` is set.
Init downloads the latest templates from GitHub.

Common flags:
- `--path <dir>`: target directory (defaults to current directory)
- `--force`: overwrite existing files
- `--branch <name>`: use a specific branch of the kit
- `--quiet`: minimal output
- `--dry-run`: show planned changes without writing files

### update
Update `.agent/` to the latest version of the kit.

Common flags:
- `--path <dir>`
- `--force`
- `--branch <name>`
- `--quiet`
- `--dry-run`

### status
Show whether `.agent/` is up to date and summarize changes.

Common flags:
- `--path <dir>`
- `--quiet`

Output includes:
- Installation status (installed / not installed)
- Current version
- Agent count
- Skill count
- Workflow count

## Examples

- Initialize in a specific folder:
  - `npx @vudovn/ag-kit init --path C:\Projects\demo`
- Dry-run update:
  - `npx @vudovn/ag-kit update --dry-run`
- Update from a branch:
  - `npx @vudovn/ag-kit update --branch main`

## Notes

- Run from the project root unless `--path` is set.
- `--force` can overwrite local changes. The `update` command deletes and replaces `.agent/`.
- `--branch` defaults to `main` when not specified.
- Back up any custom edits in `.agent/` before running `update` or `init --force`.

## System Requirements

- Node.js 16 or later
- npm or yarn
- Git
