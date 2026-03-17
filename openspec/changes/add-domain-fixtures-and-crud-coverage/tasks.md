# Tasks: Add Domain Fixtures And CRUD Coverage

Source design:
`docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`

## 1. Build Logical Seed Packs

- [ ] Add factories and seed orchestration for imported/current domain clusters
- [ ] Generate at least 1,000 total records with valid relationships
- [ ] Reuse Baltic reference geography and supported locales in generated data

## 2. Verify Seed Logic

- [ ] Add coverage for minimum record volume and relationship coherence
- [ ] Add checks for valid country-city usage in geography-bearing records
- [ ] Verify existing demo/login accounts remain available

## 3. Add CRUD Regression Coverage

- [ ] Inventory all existing CRUD surfaces after the import/localization slices land
- [ ] Add feature or Livewire coverage for each CRUD surface
- [ ] Include authorization and tenant-isolation regressions where relevant

## 4. Final Verification

- [ ] Run the expanded seed suite and focused CRUD tests
- [ ] Run `pint`
- [ ] Prepare the repository for implementation execution by chunk
