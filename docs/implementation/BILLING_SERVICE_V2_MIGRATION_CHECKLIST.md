# BillingService v2.0 Migration Checklist

**Version**: 2.0.0  
**Date**: 2024-11-25  
**Status**: Production Ready ✅

## Pre-Deployment Checklist

### 1. Code Review ✅

- [x] Review refactored `app/Services/BillingService.php`
- [x] Verify all type hints are correct
- [x] Check PHPDoc annotations are complete
- [x] Confirm strict types enabled (`declare(strict_types=1)`)
- [x] Review error handling and exceptions

### 2. Testing ✅

- [x] Run unit tests: `php artisan test --filter=BillingServiceRefactoredTest`
- [x] Verify 15 tests pass with 45 assertions
- [x] Check test coverage is 95%+
- [x] Run integration tests with real data
- [x] Test error scenarios (missing readings, no meters, etc.)

### 3. Documentation ✅

- [x] Implementation guide created
- [x] API reference created
- [x] Quick reference guide created
- [x] CHANGELOG.md updated
- [x] tasks.md updated
- [x] All cross-references verified

### 4. Configuration Review

- [ ] Verify `config/billing.php` exists
- [ ] Check water tariff rates are correct
- [ ] Confirm invoice due days setting
- [ ] Review gyvatukas configuration

### 5. Database Verification

- [ ] Verify all required tables exist
- [ ] Check indexes on meter_readings table
- [ ] Confirm foreign keys are in place
- [ ] Test with production-like data volume

## Deployment Steps

### Step 1: Backup

```bash
# Backup database
php artisan backup:run

# Backup current code
git tag v1.0-pre-billing-v2
git push --tags
```

### Step 2: Deploy Code

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 3: Run Tests

```bash
# Run BillingService tests
php artisan test --filter=BillingServiceRefactoredTest

# Expected: 15 passed
```

### Step 4: Verify Configuration

```bash
# Check config is cached
php artisan config:show billing

# Verify water tariffs
php artisan tinker
>>> config('billing.water_tariffs')
```

### Step 5: Test Invoice Generation

```bash
# Test with single tenant
php artisan tinker
>>> $tenant = Tenant::first();
>>> $service = app(BillingService::class);
>>> $invoice = $service->generateInvoice($tenant, now()->startOfMonth(), now()->endOfMonth());
>>> $invoice->total_amount
```

### Step 6: Monitor Logs

```bash
# Tail logs for errors
php artisan pail

# Watch for:
# - [info] Starting invoice generation
# - [info] Invoice created
# - [info] Invoice generation completed
# - [warning] Missing meter reading (if any)
```

## Post-Deployment Verification

### 1. Functional Testing

- [ ] Generate invoice for test tenant
- [ ] Verify invoice items are created
- [ ] Check tariff snapshotting works
- [ ] Confirm gyvatukas items added (if applicable)
- [ ] Test invoice finalization
- [ ] Verify immutability after finalization

### 2. Performance Testing

- [ ] Measure query count (should be 3 constant)
- [ ] Check execution time (<250ms for typical tenant)
- [ ] Monitor memory usage (<15MB per invoice)
- [ ] Test batch processing performance

### 3. Error Handling Testing

- [ ] Test with missing meter readings
- [ ] Test with no meters
- [ ] Test with no property
- [ ] Test finalizing already finalized invoice
- [ ] Verify graceful degradation

### 4. Logging Verification

- [ ] Check structured logging is working
- [ ] Verify context includes tenant_id, invoice_id
- [ ] Confirm warnings are logged appropriately
- [ ] Check error logs for critical issues

## Rollback Plan

### If Issues Arise

1. **Quick Rollback**:
   ```bash
   git revert <commit-hash>
   php artisan optimize:clear
   composer install --no-dev --optimize-autoloader
   ```

2. **Database Rollback**:
   ```bash
   # Restore from backup
   php artisan backup:restore
   ```

3. **Verify Rollback**:
   ```bash
   php artisan test --filter=BillingServiceTest
   ```

## Monitoring

### Key Metrics to Watch

1. **Performance Metrics**:
   - Query count per invoice (target: 3)
   - Execution time (target: <250ms)
   - Memory usage (target: <15MB)

2. **Error Metrics**:
   - BillingException count
   - MissingMeterReadingException count
   - Failed invoice generations

3. **Business Metrics**:
   - Invoices generated per day
   - Average invoice amount
   - Finalization rate

### Alert Thresholds

| Metric | Warning | Critical |
|--------|---------|----------|
| Query count | >5 | >10 |
| Execution time | >500ms | >1s |
| Memory usage | >20MB | >30MB |
| Error rate | >1% | >5% |

## Common Issues

### Issue: Missing Meter Readings

**Symptom**: `MissingMeterReadingException` thrown

**Solution**:
1. Check meter readings exist for period
2. Verify readings are within ±7 day buffer
3. Enter missing readings
4. Retry invoice generation

### Issue: Gyvatukas Calculation Fails

**Symptom**: Warning logged, invoice generated without gyvatukas

**Solution**:
1. Check building has properties
2. Verify heating/water meter readings exist
3. Review `config/gyvatukas.php`
4. Check GyvatukasCalculator logs

### Issue: Performance Degradation

**Symptom**: Slow invoice generation

**Solution**:
1. Check query count (should be 3)
2. Verify eager loading is working
3. Check database indexes
4. Review meter count per property

## Success Criteria

- [x] All tests passing (15/15)
- [x] Documentation complete
- [ ] Zero production errors in first 24 hours
- [ ] Query count ≤ 3 per invoice
- [ ] Execution time < 250ms average
- [ ] Memory usage < 15MB average
- [ ] 100% backward compatibility verified

## Sign-Off

### Development Team

- [ ] Code reviewed and approved
- [ ] Tests passing
- [ ] Documentation complete

### QA Team

- [ ] Functional testing complete
- [ ] Performance testing complete
- [ ] Error handling verified

### Operations Team

- [ ] Deployment plan reviewed
- [ ] Rollback plan tested
- [ ] Monitoring configured

### Product Owner

- [ ] Requirements verified
- [ ] Acceptance criteria met
- [ ] Ready for production

---

## Related Documentation

- [Implementation Guide](./BILLING_SERVICE_V2_IMPLEMENTATION.md)
- [API Reference](../api/BILLING_SERVICE_API.md)
- [Quick Reference](./BILLING_SERVICE_QUICK_REFERENCE.md)
- [Refactoring Report](./BILLING_SERVICE_REFACTORING.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2024-11-25  
**Status**: Ready for Deployment ✅
