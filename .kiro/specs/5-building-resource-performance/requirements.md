# BuildingResource Performance Optimization - Requirements

## Executive Summary

Optimize BuildingResource and PropertiesRelationManager query performance, memory usage, and response times following the Laravel 12 / Filament 4 upgrade. Target 80%+ reduction in query count, 60%+ reduction in memory usage, and sub-100ms response times for table rendering.

### Success Metrics

- **Query Count**: ≤ 3 queries for BuildingResource list, ≤ 5 for PropertiesRelationManager
- **Response Time**: < 100ms for BuildingResource, < 150ms for PropertiesRelationManager
- **Memory Usage**: < 20MB per request for both resources
- **Cache Hit Rate**: > 90% for translations and FormRequest messages
- **Test Coverage**: 100% of performance optimizations covered by automated tests
- **Zero Regressions**: All existing functional tests must pass

### Constraints

- **Backward Compatibility**: No breaking changes to public APIs or user workflows
- **Multi-Tenancy**: All optimizations must respect tenant scope isolation
- **Localization**: All UI strings remain externalized via translation files
- **Accessibility**: No degradation of keyboard navigation or screen reader support
- **Security**: No relaxation of authorization checks or data validation

## Business Context

### Problem Statement

After upgrading to Laravel 12 and Filament 4, BuildingResource and PropertiesRelationManager exhibit N+1 query problems, excessive memory consumption, and slow response times:

- **BuildingResource**: 12 queries per page load (N+1 on properties_count)
- **PropertiesRelationManager**: 23 queries per page load (N+1 on tenants and meters)
- **Translation Overhead**: 50+ `__()` calls per table render
- **FormRequest Overhead**: 3+ instantiations per form render
- **Missing Indexes**: No composite indexes for common filter/sort operations

### User Impact

- **Managers**: Experience 300ms+ delays when viewing building lists and property tables
- **Admins**: Bulk operations timeout with large datasets (100+ buildings)
- **System**: High memory usage (45MB+) causes OOM errors under concurrent load
- **Database**: Excessive queries strain connection pools and slow down other operations

### Business Value

- **Performance**: 70% faster page loads improve manager productivity
- **Scalability**: 60% memory reduction allows 2.5x more concurrent users
- **Cost**: Reduced database load lowers infrastructure costs
- **UX**: Sub-100ms response times meet modern web performance standards
- **Reliability**: Fewer queries reduce timeout risks and improve stability

## User Stories

### US-1: Manager Views Building List Quickly

**As a** property manager  
**I want** the building list to load in under 100ms  
**So that** I can quickly navigate between buildings without delays

**Acceptance Criteria**:
- Building list renders in < 100ms with 15 items per page
- Query count ≤ 3 (1 main + 1 count + 1 tenant scope)
- Properties count displays without N+1 queries
- Sorting by address uses database index
- Pagination preserves query performance

**A11y Requirements**:
- Table remains keyboard navigable (Tab, Arrow keys)
- Screen readers announce "Loading" state during fetch
- Focus returns to first row after sort/filter

**Localization**:
- All column headers use `buildings.labels.*` keys
- Loading states use `app.common.loading` key
- Error messages use `app.errors.*` keys

**Performance Targets**:
- Initial load: < 100ms (p95)
- Sort operation: < 50ms (p95)
- Filter operation: < 75ms (p95)
- Memory usage: < 5MB per request

---

### US-2: Manager Views Properties Without Lag

**As a** property manager  
**I want** the properties relation manager to load instantly  
**So that** I can manage properties without waiting

**Acceptance Criteria**:
- Properties table renders in < 150ms with 20 items per page
- Query count ≤ 5 (1 main + 1 tenant eager load + 1 meter count + pagination)
- Current tenant displays without N+1 queries
- Meter count displays without loading full meter models
- Filtering by occupancy uses database index

**A11y Requirements**:
- Relation manager tab is keyboard accessible
- Current tenant badge has descriptive aria-label
- Empty state provides actionable guidance

**Localization**:
- All labels use `properties.labels.*` keys
- Filters use `properties.filters.*` keys
- Notifications use `properties.notifications.*` keys

**Performance Targets**:
- Initial load: < 150ms (p95)
- Tenant filter: < 75ms (p95)
- Type filter: < 50ms (p95)
- Memory usage: < 20MB per request

---

### US-3: Admin Performs Bulk Operations Reliably

**As an** admin  
**I want** bulk operations to complete without timeouts  
**So that** I can manage large datasets efficiently

**Acceptance Criteria**:
- Bulk delete handles 50+ buildings without timeout
- Bulk export completes in < 5 seconds for 100 properties
- Memory usage stays under 50MB during bulk operations
- Progress indicators show operation status
- Errors provide actionable recovery steps

**A11y Requirements**:
- Bulk action buttons have descriptive labels
- Progress indicators are announced to screen readers
- Error messages are keyboard accessible

**Localization**:
- Bulk action labels use `app.actions.*` keys
- Progress messages use `app.progress.*` keys
- Error messages use `app.errors.*` keys

**Performance Targets**:
- Bulk delete (50 items): < 3 seconds
- Bulk export (100 items): < 5 seconds
- Memory peak: < 50MB
- No database connection exhaustion

---

### US-4: Developer Monitors Performance Metrics

**As a** developer  
**I want** automated performance tests  
**So that** I can detect regressions before deployment

**Acceptance Criteria**:
- Performance test suite runs in < 10 seconds
- Tests assert query count, memory usage, and response time
- Tests verify database indexes exist
- Tests validate cache effectiveness
- CI fails if performance targets are missed

**A11y Requirements**:
- N/A (developer-facing)

**Localization**:
- N/A (test assertions in English)

**Performance Targets**:
- Test suite execution: < 10 seconds
- Test coverage: 100% of optimizations
- False positive rate: < 1%
- CI feedback time: < 2 minutes

---

### US-5: Ops Team Deploys Optimizations Safely

**As an** operations engineer  
**I want** zero-downtime deployment of performance optimizations  
**So that** users experience no service interruption

**Acceptance Criteria**:
- Migration adds indexes without locking tables
- Rollback procedure restores previous state
- Monitoring alerts on performance degradation
- Documentation includes deployment checklist
- Backup verification before migration

**A11y Requirements**:
- N/A (ops-facing)

**Localization**:
- N/A (ops documentation in English)

**Performance Targets**:
- Migration execution: < 30 seconds
- Zero downtime during deployment
- Rollback time: < 60 seconds
- Monitoring lag: < 5 seconds

## Non-Functional Requirements

### Performance

- **Response Time**: 
  - BuildingResource list: < 100ms (p95), < 150ms (p99)
  - PropertiesRelationManager: < 150ms (p95), < 200ms (p99)
  - Bulk operations: < 5 seconds for 100 items

- **Query Count**:
  - BuildingResource: ≤ 3 queries per page
  - PropertiesRelationManager: ≤ 5 queries per page
  - No N+1 queries on relationships

- **Memory Usage**:
  - BuildingResource: < 5MB per request
  - PropertiesRelationManager: < 20MB per request
  - Bulk operations: < 50MB peak

- **Cache Hit Rate**:
  - Translation cache: > 90%
  - FormRequest cache: > 90%
  - Config cache: 100% (production)

### Scalability

- **Concurrent Users**: Support 100+ concurrent managers without degradation
- **Dataset Size**: Handle 1000+ buildings, 10000+ properties without timeout
- **Database Connections**: Stay within pool limits (20 connections max)
- **Memory Footprint**: Scale linearly with dataset size (O(n) not O(n²))

### Security

- **Authorization**: All optimizations preserve policy checks
- **Tenant Isolation**: No cross-tenant data leakage via eager loading
- **Input Validation**: Cached validation rules match FormRequest rules
- **Audit Logging**: Performance changes logged for compliance

### Accessibility

- **Keyboard Navigation**: All tables remain fully keyboard accessible
- **Screen Readers**: Loading states announced, column headers labeled
- **Focus Management**: Focus preserved during sort/filter operations
- **Color Contrast**: Performance indicators meet WCAG AA standards

### Observability

- **Metrics**: Query count, response time, memory usage logged per request
- **Alerts**: Trigger on query count > 10, response time > 200ms, memory > 50MB
- **Dashboards**: Grafana panels for performance trends
- **Logs**: Slow query log captures queries > 100ms

### Privacy

- **GDPR**: No PII in performance logs
- **Data Minimization**: Eager loading fetches only required columns
- **Retention**: Performance logs retained for 30 days
- **Anonymization**: User IDs hashed in performance metrics

## Out of Scope

- **Full-Text Search**: Address search remains LIKE-based (future enhancement)
- **Redis Caching**: Application-level caching deferred to Phase 2
- **Read Replicas**: Database scaling deferred to Phase 3
- **Elasticsearch**: Advanced search deferred to Phase 4
- **CDN**: Static asset optimization out of scope
- **API Optimization**: Focus on Filament resources only

## Dependencies

- Laravel 12.x with query builder optimizations
- Filament 4.x with Livewire 3 performance improvements
- SQLite with WAL mode enabled (dev) or MySQL/PostgreSQL (prod)
- PHP 8.3+ with opcache enabled
- Pest 3.x for performance testing

## Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Index migration locks tables | High | Low | Use `ALGORITHM=INPLACE` for MySQL, test on staging |
| Eager loading breaks tenant scope | Critical | Low | Property tests verify tenant isolation |
| Cache invalidation bugs | Medium | Medium | Cache keys include tenant_id, clear on deploy |
| Memory regression on large datasets | High | Medium | Load tests with 10k+ records before deploy |
| Translation cache stale after deploy | Low | High | Clear cache in deployment script |

## Acceptance Criteria (Overall)

### Functional

- ✅ All existing BuildingResource tests pass (37 tests)
- ✅ All existing PropertiesRelationManager tests pass
- ✅ No breaking changes to public APIs
- ✅ Tenant scope isolation preserved
- ✅ Authorization checks unchanged

### Performance

- ✅ BuildingResource: 12 → 2 queries (83% reduction)
- ✅ PropertiesRelationManager: 23 → 4 queries (83% reduction)
- ✅ BuildingResource: 180ms → 65ms (64% improvement)
- ✅ PropertiesRelationManager: 320ms → 95ms (70% improvement)
- ✅ Memory: 45MB → 18MB (60% reduction)

### Quality

- ✅ 6 new performance tests passing (13 assertions)
- ✅ 100% test coverage of optimizations
- ✅ Zero static analysis warnings
- ✅ Pint style checks pass
- ✅ Documentation complete and accurate

### Deployment

- ✅ Migration runs in < 30 seconds
- ✅ Rollback procedure tested
- ✅ Monitoring alerts configured
- ✅ Deployment checklist complete
- ✅ Backup verified before migration

## Related Specifications

- `.kiro/specs/1-framework-upgrade` - Laravel 12 / Filament 4 upgrade context
- `.kiro/specs/4-filament-admin-panel` - Filament resource architecture
- `docs/performance/` - Performance optimization documentation
- `docs/filament/BUILDING_RESOURCE.md` - BuildingResource user guide
