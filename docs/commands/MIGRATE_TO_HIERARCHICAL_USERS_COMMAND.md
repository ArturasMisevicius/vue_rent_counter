# MigrateToHierarchicalUsersCommand Documentation

## Overview

The `MigrateToHierarchicalUsersCommand` is an Artisan command that migrates existing users to the new hierarchical user management structure. This command is essential for transitioning from the old user system to the new three-tier hierarchy (Superadmin → Admin → Tenant).

## Command Signature

```bash
php artisan users:migrate-hierarchical [options]
```

## Options

- `--dry-run`: Preview changes without making any modifications to the database
- `--rollback`: Revert the migration changes (converts admin back to manager, removes subscriptions)

## What It Does

The migration command performs the following operations:

### 1. Role Migration
- Converts all users with `manager` role to `admin` role
- Preserves existing `admin`, `superadmin`, and `tenant` roles

### 2. Tenant ID Assignment
- Assigns unique `tenant_id` to admin/manager users who don't have one
- Preserves existing `tenant_id` values
- Uses auto-increment logic to ensure uniqueness

### 3. Organization Name
- Sets `organization_name` for admin users if not already set
- Uses format: `{User Name}'s Organization`

### 4. Account Activation
- Sets `is_active = true` for all existing users
- Ensures all users can log in after migration

### 5. Subscription Creation
- Creates default active subscriptions for all admin users
- Uses Professional plan with 1-year expiration
- Subscription limits:
  - Max properties: 50
  - Max tenants: 200

## Usage Examples

### Preview Changes (Dry Run)

```bash
php artisan users:migrate-hierarchical --dry-run
```

This will show you what changes would be made without actually modifying the database.

### Execute Migration

```bash
php artisan users:migrate-hierarchical
```

This will perform the actual migration with database transaction support.

### Rollback Migration

```bash
php artisan users:migrate-hierarchical --rollback
```

This will:
- Convert admin roles back to manager
- Remove all subscriptions
- Clear tenant_id from all users
- Clear organization_name from all users

## Output Example

```
Starting migration to hierarchical user structure...

Found 2 users to migrate

✓ User #10 (manager@test.com):
  - Role: manager → admin
  - Tenant ID: null → 1
  - Organization: Test Manager's Organization
  - Active: true
  - Subscription: Professional plan (expires 2026-11-26)

✓ User #20 (admin@test.com):
  - Role: admin → admin
  - Tenant ID: 2 (unchanged)
  - Organization: Admin's Organization
  - Active: true
  - Subscription: Professional plan (expires 2026-11-26)

Setting is_active = true for 1 users...
✓ User #17 (deactivated@example.com) set to active

Creating subscriptions for 3 admin users...
✓ User #1 (user1@example.org) - Subscription created
✓ User #2 (user2@example.net) - Subscription created
✓ User #3 (user3@example.net) - Subscription created

Migration completed successfully!

Summary:
  - Users migrated: 2
  - Subscriptions created: 5
  - Inactive users activated: 1
```

## Safety Features

### Transaction Support
All database operations are wrapped in a transaction. If any error occurs, all changes are rolled back automatically.

### Dry Run Mode
Use `--dry-run` to preview changes before executing them. This is highly recommended for production environments.

### Idempotent Operations
The command can be run multiple times safely:
- Won't create duplicate subscriptions
- Won't reassign tenant_id if already set
- Won't overwrite existing organization names

### Rollback Support
The `--rollback` option allows you to revert changes if needed. It includes a confirmation prompt to prevent accidental rollbacks.

## Requirements Validated

This command validates the following requirements:

- **Requirement 2.2**: Assigns unique tenant_id for data isolation
- **Requirement 3.2**: Ensures all admin accounts have proper tenant_id

## Testing

Comprehensive test suite available at:
- `tests/Feature/Commands/MigrateToHierarchicalUsersCommandTest.php`
- `tests/Feature/MigrateToHierarchicalUsersCommandTest.php`

Test coverage includes:
- Dry run mode functionality
- Role conversion (manager → admin)
- Unique tenant_id assignment
- Subscription creation
- Account activation
- Organization name setting
- Existing tenant_id preservation
- Rollback functionality
- Error handling

## Best Practices

### Before Migration

1. **Backup your database** before running the migration
2. **Run in dry-run mode** first to preview changes:
   ```bash
   php artisan users:migrate-hierarchical --dry-run
   ```
3. **Review the output** to ensure expected changes
4. **Test in staging** environment before production

### During Migration

1. **Run during maintenance window** to avoid user disruption
2. **Monitor the output** for any errors or warnings
3. **Verify transaction completion** before proceeding

### After Migration

1. **Verify user roles** are correctly updated
2. **Check subscriptions** are created for all admins
3. **Test user login** for different roles
4. **Verify data isolation** is working correctly
5. **Update documentation** if needed

## Troubleshooting

### Migration Fails

If the migration fails:
1. Check the error message in the output
2. Verify database connection
3. Ensure all required tables exist (users, subscriptions)
4. Check for any database constraints that might be violated
5. Review the transaction rollback message

### Rollback Issues

If rollback fails:
1. Check if subscriptions table exists
2. Verify foreign key constraints
3. Ensure no other processes are modifying user data
4. Review the error message for specific issues

### Duplicate Subscriptions

The command prevents duplicate subscriptions by checking if a subscription already exists before creating a new one. If you encounter duplicates:
1. Check if subscriptions were created outside this command
2. Verify the subscription relationship in the User model
3. Run the command again - it should skip existing subscriptions

## Related Documentation

- [Hierarchical User Management Requirements](.kiro/specs/3-hierarchical-user-management/requirements.md)
- [Hierarchical User Management Design](.kiro/specs/3-hierarchical-user-management/design.md)
- [User Model Documentation](../api/USER_MODEL_API.md)
- [Subscription Model Documentation](../api/SUBSCRIPTION_MODEL_API.md)

## Implementation Details

**File**: `app/Console/Commands/MigrateToHierarchicalUsersCommand.php`

**Dependencies**:
- `App\Models\User`
- `App\Models\Subscription`
- `App\Enums\UserRole`
- `App\Enums\SubscriptionPlanType`
- `App\Enums\SubscriptionStatus`

**Database Tables Modified**:
- `users` (role, tenant_id, organization_name, is_active)
- `subscriptions` (new records created)

## Version History

- **2024-11-26**: Initial implementation with full feature set
  - Role migration (manager → admin)
  - Tenant ID assignment
  - Subscription creation
  - Account activation
  - Dry run and rollback support
  - Comprehensive test coverage
