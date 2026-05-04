---
name: 21st-dev-design
description: "Use this skill for UI design and redesign work where 21st.dev Magic MCP should provide inspiration search, SVG icon search, or generated UI variants. Trigger when the user mentions 21st.dev, Magic MCP, design skill, redesign, layout, component inspiration, UI variants, icons, logos, or design updates for Blade, Livewire, Filament, or Tailwind interfaces."
license: MIT
metadata:
  author: 21st.dev-adapted
---

# 21st.dev Design

Use this skill when a design task should benefit from the repo-local `21st-dev-magic` MCP server.

## Requirements

- `.mcp.json` must include `21st-dev-magic`.
- The host agent/editor process must expose `TWENTY_FIRST_DEV_API_KEY`.
- Keep secrets out of repository files.

## Available 21st.dev Magic MCP Capabilities

- Inspiration Search: find relevant UI examples and patterns before writing code.
- SVG Icon Search: find brand SVG icons and insert appropriate icons.
- Magic Generate: generate polished UI variants for a component or layout.

## Workflow

1. Inspect the existing Blade, Livewire, Filament, and Tailwind structure before asking Magic for ideas.
2. Use Inspiration Search for comparable layouts, empty states, dashboards, navigation, forms, tables, and role-specific pages.
3. Use SVG Icon Search only when a real logo or recognizable brand icon is needed.
4. Use Magic Generate when the user wants design alternatives or when a component needs a substantial redesign.
5. Adapt the result to this project; do not paste generated code blindly.
6. Preserve Laravel rules: no queries in Blade, no business logic in views, and no React/Vue/Inertia.
7. Implement with Blade/Livewire/Filament/Tailwind patterns already present in the codebase.
8. Verify the UI in-browser after significant frontend changes.

## Design Guardrails

- Build the actual usable interface, not a marketing page, unless the user asks for marketing.
- Keep operational tenant/admin screens dense, calm, scannable, and task-focused.
- Avoid nested cards, decorative gradient blobs, one-note palettes, and text that overlaps on mobile.
- Use stable responsive dimensions for repeated controls, tables, tiles, and dashboard panels.
- Prefer existing project components, tokens, icons, and spacing before introducing new abstractions.

## Output Expectations

When reporting design work, state:

- Which 21st.dev capability was used or why it was unavailable.
- Which files changed.
- How the design was verified.
