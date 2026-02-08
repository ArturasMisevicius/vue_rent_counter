---
inclusion: always
---


# ILO CODE – Testing Rules
- 100% test coverage mandatory
- Only Pest PHP
- Feature tests use `actingAs($user)->postJson()` pattern
- Use RefreshDatabase + DatabaseTransactions together
- Every Action class has its own test
- Every Value Object has unit test
- Browser tests with Pest + Livewire testing syntax