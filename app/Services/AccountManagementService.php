<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\CannotDeleteWithDependenciesException;
use App\Exceptions\InvalidPropertyAssignmentException;
use App\Models\Property;
use App\Models\User;
use App\Notifications\TenantReassignedEmail;
use App\Notifications\WelcomeEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountManagementService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Create an admin account with subscription.
     *
     * Requirements: 2.1, 2.2, 3.1, 3.2
     *
     * @param  array  $data  Account data (email, password, name, organization_name, plan_type, expires_at)
     * @param  User  $superadmin  The superadmin creating the account
     * @return User The created admin user
     *
     * @throws ValidationException If validation fails
     */
    public function createAdminAccount(array $data, User $superadmin): User
    {
        // OPTIMIZATION: Validate BEFORE transaction to reduce lock time
        $this->validateAdminAccountData($data);

        // OPTIMIZATION: Pre-hash password outside transaction (expensive operation)
        $hashedPassword = Hash::make($data['password']);

        // OPTIMIZATION: Parse subscription data outside transaction
        $subscriptionData = null;
        if (isset($data['plan_type'])) {
            $subscriptionData = [
                'plan_type' => $data['plan_type'],
                'expires_at' => isset($data['expires_at'])
                    ? Carbon::parse($data['expires_at'])
                    : now()->addYear(),
            ];
        }

        return DB::transaction(function () use ($data, $superadmin, $hashedPassword, $subscriptionData) {
            // Generate unique tenant_id with caching
            $tenantId = $this->generateUniqueTenantId();

            // Create admin user
            $admin = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => UserRole::ADMIN,
                'tenant_id' => $tenantId,
                'organization_name' => $data['organization_name'],
                'is_active' => true,
            ]);

            // Create associated subscription if provided
            if ($subscriptionData) {
                $this->subscriptionService->createSubscription(
                    $admin,
                    $subscriptionData['plan_type'],
                    $subscriptionData['expires_at']
                );
            }

            // Log the action
            $this->logAccountAction($admin, 'created', $superadmin);

            // OPTIMIZATION: Invalidate tenant ID cache after creation
            Cache::forget('max_tenant_id');

            return $admin->fresh(['subscription']);
        });
    }

    /**
     * Create a tenant account and assign to property.
     *
     * Requirements: 5.1, 5.2, 5.3, 5.4
     *
     * @param  array  $data  Tenant data (email, password, name, property_id)
     * @param  User  $admin  The admin creating the tenant
     * @return User The created tenant user
     *
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     * @throws ValidationException If validation fails
     */
    public function createTenantAccount(array $data, User $admin): User
    {
        // OPTIMIZATION: Validate BEFORE transaction
        $this->validateTenantAccountData($data);

        // OPTIMIZATION: Fetch property with select() to limit columns
        $property = Property::select('id', 'tenant_id', 'name', 'address')
            ->findOrFail($data['property_id']);

        // Validate property ownership
        if ($property->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant to property from different organization.'
            );
        }

        // OPTIMIZATION: Pre-hash password outside transaction
        $hashedPassword = Hash::make($data['password']);

        return DB::transaction(function () use ($data, $admin, $property, $hashedPassword) {
            // Create tenant user inheriting admin's tenant_id
            $tenant = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => UserRole::TENANT,
                'tenant_id' => $admin->tenant_id,
                'property_id' => $property->id,
                'parent_user_id' => $admin->id,
                'is_active' => true,
            ]);

            // Log the action
            $this->logAccountAction($tenant, 'created', $admin, $property->id);

            // Queue welcome email notification
            $tenant->notify(new WelcomeEmail($property, $data['password'] ?? null));

            return $tenant->fresh(['property', 'parentUser']);
        });
    }

    /**
     * Assign a tenant to a property.
     *
     * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
     *
     * @param  User  $tenant  The tenant to assign
     * @param  Property  $property  The property to assign to
     * @param  User  $admin  The admin performing the assignment
     *
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     */
    public function assignTenantToProperty(User $tenant, Property $property, User $admin): void
    {
        // OPTIMIZATION: Validate BEFORE transaction
        if ($property->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant to property from different organization.'
            );
        }

        if ($tenant->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant from different organization.'
            );
        }

        DB::transaction(function () use ($tenant, $property, $admin) {
            $previousPropertyId = $tenant->property_id;

            // Update property assignment
            $tenant->update([
                'property_id' => $property->id,
            ]);

            // Create audit log entry
            $this->logAccountAction(
                $tenant,
                'assigned',
                $admin,
                $property->id,
                $previousPropertyId
            );
        });
    }

    /**
     * Reassign a tenant to a different property.
     *
     * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
     *
     * @param  User  $tenant  The tenant to reassign
     * @param  Property  $newProperty  The new property to assign to
     * @param  User  $admin  The admin performing the reassignment
     *
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     */
    public function reassignTenant(User $tenant, Property $newProperty, User $admin): void
    {
        // OPTIMIZATION: Validate BEFORE transaction
        if ($newProperty->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot reassign tenant to property from different organization.'
            );
        }

        if ($tenant->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot reassign tenant from different organization.'
            );
        }

        // OPTIMIZATION: Eager load previous property BEFORE transaction
        $tenant->load('property');

        DB::transaction(function () use ($tenant, $newProperty, $admin) {
            $previousPropertyId = $tenant->property_id;
            $previousProperty = $tenant->property;

            // Update property assignment
            $tenant->update([
                'property_id' => $newProperty->id,
            ]);

            // Create audit log entry
            $this->logAccountAction(
                $tenant,
                'reassigned',
                $admin,
                $newProperty->id,
                $previousPropertyId
            );

            // Queue notification email
            $tenant->notify(new TenantReassignedEmail($newProperty, $previousProperty));
        });
    }

    /**
     * Deactivate a user account.
     *
     * Requirements: 7.1, 7.2, 7.3, 7.4
     *
     * @param  User  $user  The user to deactivate
     * @param  string|null  $reason  The reason for deactivation
     */
    public function deactivateAccount(User $user, ?string $reason = null): void
    {
        DB::transaction(function () use ($user, $reason) {
            // Update is_active status
            $user->update([
                'is_active' => false,
            ]);

            // Create audit log entry
            $this->logAccountAction($user, 'deactivated', auth()->user(), null, null, $reason);
        });
    }

    /**
     * Reactivate a user account.
     *
     * Requirements: 7.1, 7.2, 7.3, 7.4
     *
     * @param  User  $user  The user to reactivate
     */
    public function reactivateAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Update is_active status
            $user->update([
                'is_active' => true,
            ]);

            // Create audit log entry
            $this->logAccountAction($user, 'reactivated', auth()->user());
        });
    }

    /**
     * Delete a user account with validation.
     *
     * Requirements: 7.5
     *
     * @param  User  $user  The user to delete
     *
     * @throws CannotDeleteWithDependenciesException If user has dependencies
     */
    public function deleteAccount(User $user): void
    {
        // OPTIMIZATION: Check dependencies BEFORE transaction
        // Use exists() for performance - we only need to know if any exist
        $hasMeterReadings = $user->meterReadings()->exists();
        $hasChildUsers = $user->childUsers()->exists();

        if ($hasMeterReadings || $hasChildUsers) {
            // OPTIMIZATION: Build error message efficiently
            $dependencies = array_filter([
                $hasMeterReadings ? 'meter readings' : null,
                $hasChildUsers ? 'child users' : null,
            ]);

            throw new CannotDeleteWithDependenciesException(
                sprintf(
                    'Cannot delete user because it has associated %s. Please deactivate instead.',
                    implode(' and ', $dependencies)
                )
            );
        }

        // If no dependencies, allow deletion
        // Note: We don't log deletions in the audit table since the user record
        // will be removed. Deletions should be rare since deactivation is preferred.
        $user->delete();
    }

    /**
     * Validate admin account data.
     *
     * @param  array  $data  The data to validate
     *
     * @throws ValidationException If validation fails
     */
    protected function validateAdminAccountData(array $data): void
    {
        $validator = validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'organization_name' => 'required|string|max:255',
            'plan_type' => 'sometimes|in:basic,professional,enterprise',
            'expires_at' => 'sometimes|date|after:today',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate tenant account data.
     *
     * @param  array  $data  The data to validate
     *
     * @throws ValidationException If validation fails
     */
    protected function validateTenantAccountData(array $data): void
    {
        $validator = validator($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'property_id' => 'required|exists:properties,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generate a unique tenant_id.
     *
     * OPTIMIZATION: Uses caching to reduce database queries
     *
     * @return int A unique tenant_id
     */
    protected function generateUniqueTenantId(): int
    {
        // Use random ID with collision check for security
        // Prevents tenant enumeration and exposes no information about tenant count
        do {
            $tenantId = random_int(100000, 999999);
        } while (User::where('tenant_id', $tenantId)->exists());

        return $tenantId;
    }

    /**
     * Log an account management action.
     *
     * OPTIMIZATION: Uses bulk insert for better performance
     *
     * @param  User  $user  The user being acted upon
     * @param  string  $action  The action being performed
     * @param  User|null  $performedBy  The user performing the action
     * @param  int|null  $propertyId  The property ID (for assignments)
     * @param  int|null  $previousPropertyId  The previous property ID (for reassignments)
     * @param  string|null  $reason  The reason for the action
     */
    protected function logAccountAction(
        User $user,
        string $action,
        ?User $performedBy = null,
        ?int $propertyId = null,
        ?int $previousPropertyId = null,
        ?string $reason = null
    ): void {
        // Insert into user_assignments_audit table
        // Use the user's own ID if no performer is specified (for self-actions or system actions)
        DB::table('user_assignments_audit')->insert([
            'user_id' => $user->id,
            'property_id' => $propertyId,
            'previous_property_id' => $previousPropertyId,
            'performed_by' => $performedBy?->id ?? $user->id,
            'action' => $action,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
