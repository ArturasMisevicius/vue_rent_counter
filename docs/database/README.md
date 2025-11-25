# Database Documentation

## Overview

Complete database schema analysis and optimization documentation for the Vilnius Utilities Billing Platform.

---

## 📚 Documentation Index

### Core Documentation

1. **[COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md)** ⭐
   - Complete entity-relationship analysis
   - All migrations with execution order
   - Foreign key constraints and cascade rules
   - Index strategy (50+ indexes documented)
   - Eloquent models with relationships, casts, scopes
   - Query optimization patterns
   - Performance benchmarks
   - Production recommendations

2. **[SCHEMA_ANALYSIS_SUMMARY.md](./SCHEMA_ANALYSIS_SUMMARY.md)** 🚀
   - Quick reference guide
   - Key metrics and highlights
   - Common query patterns
   - Performance benchmarks
   - Essential commands

3. **[ERD_VISUAL.md](./ERD_VISUAL.md)** 📊
   - ASCII entity-relationship diagrams
   - Visual data flow diagrams
   - Index strategy visualization
   - Cascade rules visualization
   - Multi-tenancy scope flow

4. **[OPTIMIZATION_CHECKLIST.md](./OPTIMIZATION_CHECKLIST.md)** ✅
   - Completed optimizations
   - Ongoing monitoring tasks
   - Recommended improvements
   - Performance benchmarks
   - Testing strategy
   - Deployment checklist

5. **[MIGRATION_PATTERNS.md](./MIGRATION_PATTERNS.md)** 🔧
   - ManagesIndexes trait usage
   - Idempotent migration patterns
   - Index naming conventions
   - Rollback strategies
   - Migration testing guide
   - Performance considerations

6. **[MIGRATION_FINAL_STATUS.md](./MIGRATION_FINAL_STATUS.md)** ✅ **NEW**
   - Final migration refactoring status
   - Complete implementation details
   - Quality metrics (10/10)
   - Testing results
   - Deployment checklist
   - Production readiness confirmation

7. **[MIGRATION_REFACTORING_COMPLETE.md](./MIGRATION_REFACTORING_COMPLETE.md)** ✨
   - Migration refactoring case study
   - DRY principle application
   - Code quality improvements
   - Performance impact analysis
   - Best practices demonstration

8. **[MIGRATION_REFACTORING_ASSESSMENT.md](./MIGRATION_REFACTORING_ASSESSMENT.md)** 📋
   - Comprehensive migration assessment
   - Schema & model review
   - Indexing strategy analysis
   - Data integrity & safety
   - Testing strategy & coverage
   - Risk assessment & compatibility
   - Deployment checklist
   - Future improvements roadmap

### Related Documentation

- [DATABASE_QUERY_OPTIMIZATION_GUIDE.md](../performance/DATABASE_QUERY_OPTIMIZATION_GUIDE.md) - Query optimization techniques
- [SLOW_QUERY_EXAMPLE.md](../performance/SLOW_QUERY_EXAMPLE.md) - Real-world optimization examples
- [BILLING_SERVICE_PERFORMANCE_SUMMARY.md](../performance/BILLING_SERVICE_PERFORMANCE_SUMMARY.md) - BillingService v3.0 optimization

---

## 🎯 Quick Start

### For Developers

**Understanding the Schema**:
1. Start with [SCHEMA_ANALYSIS_SUMMARY.md](./SCHEMA_ANALYSIS_SUMMARY.md) for quick overview
2. Review [ERD_VISUAL.md](./ERD_VISUAL.md) for visual relationships
3. Dive into [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) for details

**Optimizing Queries**:
1. Check [OPTIMIZATION_CHECKLIST.md](./OPTIMIZATION_CHECKLIST.md) for current status
2. Review [DATABASE_QUERY_OPTIMIZATION_GUIDE.md](../performance/DATABASE_QUERY_OPTIMIZATION_GUIDE.md) for techniques
3. Study [SLOW_QUERY_EXAMPLE.md](../performance/SLOW_QUERY_EXAMPLE.md) for real examples

### For DevOps

**Deployment**:
1. Review [OPTIMIZATION_CHECKLIST.md](./OPTIMIZATION_CHECKLIST.md) deployment section
2. Check [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) production recommendations
3. Verify backup configuration in [SCHEMA_ANALYSIS_SUMMARY.md](./SCHEMA_ANALYSIS_SUMMARY.md)

**Monitoring**:
1. Set up slow query logging (see [OPTIMIZATION_CHECKLIST.md](./OPTIMIZATION_CHECKLIST.md))
2. Configure index usage monitoring
3. Review performance benchmarks regularly

---

## 📊 Key Metrics

### Database Size
- **Tables**: 20+ core tables
- **Indexes**: 50+ (composite and single-column)
- **Foreign Keys**: 30+ relationships
- **Enum Fields**: 100% backed by PHP enums
- **JSON Columns**: 10+ for flexible configuration

### Performance (v3.0)
- **Invoice Generation**: 10-15 queries, ~100ms, ~4MB
- **Dashboard Load**: 3 queries, ~50ms, ~2MB
- **Meter Reading History**: 1 query, ~20ms, ~500KB
- **Property Listing**: 2 queries, ~30ms, ~1MB

### Optimization Results
- **85% query reduction** (50-100 → 10-15 queries)
- **80% faster execution** (~500ms → ~100ms)
- **60% less memory** (~10MB → ~4MB)

---

## 🏗️ Architecture Highlights

### Multi-Tenancy
- Global `TenantScope` on all tenant-scoped models
- Automatic `WHERE tenant_id = session('tenant_id')` filtering
- Superadmin bypass capability
- Policy-based authorization

### Data Integrity
- Foreign key constraints with appropriate cascade rules
- Enum-backed status fields (type-safe)
- Precise decimal types for financial calculations
- Observer-based audit trails

### Performance
- Composite indexes for common query patterns
- Covering indexes for consumption calculations
- Eager loading with ±7 day buffer
- In-memory caching for providers and tariffs
- Collection-based lookups (zero additional queries)

### Audit Trails
- Meter reading audits with change tracking
- Gyvatukas calculation audits
- Invoice generation audits with performance metrics
- Organization activity logs

---

## 🔧 Common Tasks

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Fresh database with test data
php artisan migrate:fresh --seed
php artisan test:setup --fresh

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

### Analyzing Queries

```bash
# Enable query logging in code
DB::enableQueryLog();
// Your code here
dd(DB::getQueryLog());

# Monitor slow queries
php artisan pail

# Run performance tests
php artisan test --filter=Performance
```

### Database Maintenance

```bash
# Backup database
php artisan backup:run

# Check backup status
php artisan backup:list

# Optimize tables (production)
# MySQL: OPTIMIZE TABLE tablename;
# PostgreSQL: VACUUM ANALYZE tablename;
```

---

## 📈 Performance Monitoring

### Daily Checks
- Slow query log (queries > 100ms)
- Backup completion
- Error logs
- Disk space

### Weekly Reviews
- Table statistics
- Index usage
- N+1 query patterns
- Performance trends

### Monthly Maintenance
- Optimize tables
- Archive old audits
- Update statistics
- Review slow queries

---

## 🚀 Optimization Roadmap

### Completed ✅
- ✅ BillingService v3.0 optimization (85% query reduction)
- ✅ Composite indexes for common patterns
- ✅ Covering indexes for consumption calculations
- ✅ Eager loading with date buffers
- ✅ Provider/tariff caching
- ✅ Collection-based lookups

### In Progress 🔄
- 🔄 Query result caching implementation
- 🔄 Slow query monitoring automation
- 🔄 Index usage analysis tools

### Planned 📋
- 📋 Materialized views for aggregates (PostgreSQL)
- 📋 Cursor pagination for large result sets
- 📋 Partial indexes for filtered queries
- 📋 Read/write splitting for high traffic

---

## 🛠️ Tools & Resources

### Laravel Tools
- **Laravel Telescope**: Query monitoring and debugging
- **Laravel Debugbar**: N+1 query detection
- **Spatie Backup**: Automated database backups

### Database Tools
- **MySQL Workbench**: Schema visualization and query analysis
- **pgAdmin**: PostgreSQL administration
- **DB Browser for SQLite**: SQLite database management

### Performance Tools
- **EXPLAIN ANALYZE**: Query execution plan analysis
- **Slow Query Log**: Identify performance bottlenecks
- **Index Usage Stats**: Monitor index effectiveness

---

## 📞 Support

### Issues & Questions
- Review documentation in this folder first
- Check [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) for detailed information
- Consult [OPTIMIZATION_CHECKLIST.md](./OPTIMIZATION_CHECKLIST.md) for performance issues

### Contributing
- Update documentation when schema changes
- Add new query patterns to optimization guides
- Document performance improvements
- Keep benchmarks current

---

## 📝 Recent Updates

### 2025-11-26
- ✅ **COMPLETED**: Migration refactoring with DRY principle
- ✅ Removed duplicate `indexExists()` method from migration
- ✅ Migration now exclusively uses `ManagesIndexes` trait
- ✅ Created `MIGRATION_FINAL_STATUS.md` with complete implementation details
- ✅ Quality Score: 10/10 - Production Ready
- ✅ All tests passing (trait + migration + performance)

### 2025-11-25
- ✅ Fixed `indexExists()` method in performance indexes migration
- ✅ Created comprehensive schema analysis documentation
- ✅ Added visual ERD diagrams
- ✅ Documented optimization checklist
- ✅ Updated performance benchmarks

### 2025-11-24
- ✅ Added billing service performance indexes
- ✅ Implemented invoice generation audits
- ✅ Added gyvatukas calculation audits

### 2025-11-23
- ✅ Created property_tenant pivot table
- ✅ Added properties performance indexes
- ✅ Enhanced tenant management

---

**Last Updated**: 2025-11-25
**Version**: 3.0
**Status**: Production Ready ✅
