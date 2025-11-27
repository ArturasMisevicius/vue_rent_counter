# Factory Documentation Index

Complete documentation suite for the hierarchical user management factory system.

## Quick Links

### For Developers
- **[Quick Reference](./FACTORY_QUICK_REFERENCE.md)** - Fast lookup for common patterns
- **[Verification Guide](./FACTORY_VERIFICATION.md)** - How to verify factories work
- **[API Documentation](../api/FACTORY_API.md)** - Complete API reference

### For Testing
- **[Testing Guide](./TESTING_GUIDE.md)** - General testing approach
- **[Factory Tests](../../tests/Feature/)** - Actual test implementations

### For Architecture
- **[Multi-Tenancy Architecture](../architecture/MULTI_TENANCY_ARCHITECTURE.md)** - Tenant isolation patterns
- **[Hierarchical User Management](../../.kiro/specs/3-hierarchical-user-management/)** - Complete specification

## Documentation Structure

```
docs/
├── testing/
│   ├── FACTORY_VERIFICATION.md          # Comprehensive verification guide
│   ├── FACTORY_QUICK_REFERENCE.md       # Quick reference for developers
│   └── FACTORY_DOCUMENTATION_INDEX.md   # This file
├── api/
│   └── FACTORY_API.md                   # Complete API documentation
└── updates/
    └── FACTORY_VERIFICATION_UPDATE.md   # Update changelog
```

## Getting Started

### 1. Quick Start
If you just need to use factories in tests:
→ [Quick Reference](./FACTORY_QUICK_REFERENCE.md)

### 2. Verification
To verify factories are working correctly:
```bash
php test_factories.php
```
→ [Verification Guide](./FACTORY_VERIFICATION.md)

### 3. Deep Dive
For complete understanding of factory APIs:
→ [API Documentation](../api/FACTORY_API.md)

## Common Tasks

### Create Test Users

```php
// Superadmin
$superadmin = User::factory()->superadmin()->create();

// Admin with subscription
$subscription = Subscription::factory()->basic()->create();
$admin = User::factory()->admin(1)->create([
    'subscription_id' => $subscription->id,
]);

// Tenant with property
$tenant = User::factory()
    ->tenant(1, $property->id, $admin->id)
    ->create();
```

### Create Subscriptions

```php
// Basic plan
$basic = Subscription::factory()->basic()->create();

// Professional plan
$pro = Subscription::factory()->professional()->create();

// Enterprise plan
$enterprise = Subscription::factory()->enterprise()->create();
```

### Multi-Tenant Setup

```php
// Organization 1
$admin1 = User::factory()->admin(1)->create();
$property1 = Property::factory()->create(['tenant_id' => 1]);

// Organization 2 (isolated)
$admin2 = User::factory()->admin(2)->create();
$property2 = Property::factory()->create(['tenant_id' => 2]);
```

## Documentation by Role

### For New Developers
1. Start with [Quick Reference](./FACTORY_QUICK_REFERENCE.md)
2. Review common patterns
3. Run verification script
4. Refer to [API Documentation](../api/FACTORY_API.md) as needed

### For Test Writers
1. Review [Quick Reference](./FACTORY_QUICK_REFERENCE.md)
2. Check [Testing Guide](./TESTING_GUIDE.md)
3. Use [API Documentation](../api/FACTORY_API.md) for complex scenarios
4. Run [Verification](./FACTORY_VERIFICATION.md) before committing

### For Architects
1. Review [API Documentation](../api/FACTORY_API.md)
2. Understand [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY_ARCHITECTURE.md)
3. Check [Hierarchical User Management Spec](../../.kiro/specs/3-hierarchical-user-management/)
4. Review [Update Documentation](../updates/FACTORY_VERIFICATION_UPDATE.md)

## Key Concepts

### Multi-Tenancy Pattern
- **Superadmin**: `tenant_id = null` (global access)
- **Admin**: `tenant_id` set (organization-scoped)
- **Tenant**: `tenant_id` + `property_id` (property-scoped)

### Hierarchical Relationships
```
Superadmin
  └── Admin (tenant_id: 1)
      ├── Tenant 1 (property_id: 1, parent: admin.id)
      └── Tenant 2 (property_id: 2, parent: admin.id)
```

### Subscription Limits
- **Basic**: 10 properties, 50 tenants
- **Professional**: 50 properties, 250 tenants
- **Enterprise**: 999 properties, 9999 tenants

## Verification

### Quick Check
```bash
php test_factories.php
```

### Full Test Suite
```bash
php artisan test --filter=FactoryTest
```

### CI/CD Integration
```yaml
- name: Verify Factories
  run: php test_factories.php
```

## Best Practices

### ✅ Do
- Use state methods for clarity
- Set tenant_id explicitly
- Maintain hierarchical relationships
- Respect subscription limits
- Run verification before committing

### ❌ Don't
- Manually set role attributes
- Create orphaned tenants
- Exceed subscription limits in tests
- Skip tenant_id on multi-tenant models

## Troubleshooting

### Common Issues
1. **Factory not found** → Run `composer dump-autoload`
2. **Missing enum values** → Check `app/Enums/UserRole.php`
3. **Database errors** → Verify `.env` configuration

See [Verification Guide - Troubleshooting](./FACTORY_VERIFICATION.md#troubleshooting) for details.

## Related Documentation

### Testing
- [Testing Guide](./TESTING_GUIDE.md)
- [Verification Scripts](./README.md#verification-scripts)
- [Pest Documentation](https://pestphp.com/)

### Architecture
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY_ARCHITECTURE.md)
- [Hierarchical User Management](../../.kiro/specs/3-hierarchical-user-management/)
- [Database Schema](../database/README.md)

### API
- [Factory API](../api/FACTORY_API.md)
- [User Policy API](../api/USER_POLICY_API.md)
- [Subscription API](../api/SUBSCRIPTION_API.md)

## Updates

### Latest Changes
- **2024-11-26**: Initial factory verification system release
  - Created verification script
  - Added comprehensive documentation
  - Integrated with README and tasks.md

See [Update Documentation](../updates/FACTORY_VERIFICATION_UPDATE.md) for details.

## Support

### Questions?
1. Check [Quick Reference](./FACTORY_QUICK_REFERENCE.md)
2. Review [API Documentation](../api/FACTORY_API.md)
3. Run [Verification](./FACTORY_VERIFICATION.md)
4. Contact development team

### Found an Issue?
1. Run verification script
2. Check troubleshooting guide
3. Review related tests
4. Report to development team

## Contributing

When adding new factory features:
1. Update factory classes
2. Add verification to `test_factories.php`
3. Update API documentation
4. Add examples to quick reference
5. Run full test suite
6. Update this index if needed

## Version History

- **v1.0.0** (2024-11-26): Initial release
  - Factory verification script
  - Complete documentation suite
  - CI/CD integration ready

## License

This documentation is part of the Vilnius Utilities Billing System proprietary software.
