---
name: update-changelog-before-commit
description: Use when preparing to commit in this repository so CHANGELOG.md is refreshed from staged changes before the git commit is created.
---

# Update Changelog Before Commit

## Overview

This repository keeps `CHANGELOG.md` in the commit itself. Before any commit, refresh the changelog from the staged diff instead of writing ad hoc notes.

## Required Flow

1. Stage the intended code changes first.
2. Run `php scripts/update_changelog.php --mode=pending`.
3. Review `CHANGELOG.md`.
4. Stage `CHANGELOG.md`.
5. Create the commit.

The git hooks in `.githooks/` enforce the same flow automatically when `core.hooksPath` is set to `.githooks`.

## Notes

- The updater ignores `CHANGELOG.md` itself when building the file list.
- The `pre-commit` hook is the only hook that mutates `CHANGELOG.md`, because Git does not include index updates made in `commit-msg` in the current commit.
- Hook-managed entries stay as staged-change summaries inside the commit that introduced the code changes.
- Do not bypass this flow with manual changelog edits unless the generated entry is clearly wrong.
