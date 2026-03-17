# Design: Baltic Reference Localization

## Context

The repository currently seeds `en`, `lt`, `ru`, and `es`. The user request now
narrows the supported language set to `en`, `lt`, and `ru`, and requires
realistic Baltic-only countries and cities with multilingual naming.

## Goals

- Remove Spanish from supported seeded/runtime languages
- Keep English as the application fallback locale
- Add valid country/city reference data only for Lithuania, Latvia, and Estonia
- Store or import multilingual names for those countries and cities in English,
  Lithuanian, and Russian

## Non-Goals

- Global country coverage
- Additional locales beyond `en`, `lt`, and `ru`
- Hand-written city lists with unverifiable names

## Data Sources

This slice should import names from authoritative datasets:

- Unicode CLDR territory naming guidance and localized display names
- EU Vocabularies / Eurostat country references for canonical country lists
- GeoNames and/or Eurostat city datasets for valid Baltic city-country pairs

## Architecture Overview

This slice introduces:

- a narrowed supported-language seed/runtime contract
- a reference geography layer for Baltic countries and cities
- multilingual name import logic for supported locales

The implementation may use dedicated country/city models or another additive
reference-data structure, but the runtime must expose durable localized names
and valid country-city pairings.

## Testing Strategy

- verify only `en`, `lt`, and `ru` are seeded as supported languages
- verify `es` is no longer seeded or presented as supported
- verify every seeded city belongs to Lithuania, Latvia, or Estonia
- verify multilingual lookups resolve for each supported locale
