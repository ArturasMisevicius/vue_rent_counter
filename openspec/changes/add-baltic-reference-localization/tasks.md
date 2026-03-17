# Tasks: Add Baltic Reference Localization

Source design:
`docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`

## 1. Narrow Language Support

- [ ] Update language seed/runtime support to keep only `en`, `lt`, and `ru`
- [ ] Remove `es` from seeded supported-language surfaces
- [ ] Verify English fallback behavior remains intact

## 2. Introduce Baltic Reference Geography

- [ ] Add additive reference-data structure for countries and cities if missing
- [ ] Import Lithuania, Latvia, and Estonia only
- [ ] Import valid Baltic cities with correct country mappings

## 3. Add Multilingual Naming

- [ ] Import country names for `en`, `lt`, and `ru`
- [ ] Import city names for `en`, `lt`, and `ru`
- [ ] Add translation/import tests for localized geography lookup

## 4. Verify Runtime Integration

- [ ] Update seeders/factories that currently use generic geography
- [ ] Add focused feature/unit coverage for supported languages and Baltic geography
- [ ] Run focused tests and `pint`
