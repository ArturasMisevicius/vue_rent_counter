---
inclusion_mode: "conditional"
file_patterns:
  - "app/Providers/Filament/**/*.php"
  - "resources/css/filament/**"
---

# Filament Theming & Branding

## Colors & typography
- Use OKLCH palettes; set primary/semantic colors in PanelProvider (`->colors([...])`).
- Keep typography consistent (panel `->font()`); avoid mixing multiple font stacks.

## Logo & layout
- Supply SVG logos with height hints; avoid large PNGs.
- Keep sidebar collapsible; ensure brand mark works in dark/light if both are enabled.

## CSS overrides
- Prefer `viteTheme()` with CSS variables; avoid deep overrides of core classes.
- Scope custom styles to `.fi-` classes to prevent bleed.
- Maintain consistent radius/spacing/shadow tokens across cards/sections/buttons.
- Use the Tailwind 3.4+ utility set in themes: `min-h-dvh` for full-height layouts, `text-balance`/`text-pretty` for headings, `size-*` for icon sizing, `has-*` for grouped navigation states, and `forced-color-adjust-*` when respecting forced-color modes.

## Dark mode
- If enabled, test contrast on primary/danger/success; avoid pure black; use neutral surfaces.

## Do / Don’t
- ✅ Document theme tokens in `resources/css/filament/app/theme.css`.
- ✅ Keep density consistent (line-height, padding).
- ❌ Override component internals without need.
- ❌ Use inline styles for branding tweaks.
