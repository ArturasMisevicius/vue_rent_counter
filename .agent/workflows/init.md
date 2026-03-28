---
description: Reinitialize project planning. If `.planning/` exists, remove it first, then run the new-project workflow again.
---

# /init - Reinitialize Project Planning

$ARGUMENTS

---

## Task

Run project initialization from a clean planning state.

### Steps

1. **Reset stale planning artifacts**
   - Run the shared init helper with reset behavior
   - This command intentionally removes existing `.planning/` files and directories before continuing
   - Use:
     - `node .agent/get-shit-done/bin/gsd-tools.cjs init new-project --reset --raw`

2. **Re-run project initialization**
   - After cleanup, execute the same `new-project` workflow again
   - Treat `$ARGUMENTS` exactly like the normal new-project input, including `--auto` and any referenced idea document

3. **Preserve normal workflow gates**
   - Keep the same questioning, approvals, research, requirements, and roadmap flow as `new-project`

### Notes

- This command is for reinitialization, not progress/resume
- If you want to continue existing planning instead of deleting it, use the progress/resume flow instead
