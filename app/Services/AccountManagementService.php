<?php

namespace App\Services;

use App\Exceptions\CannotDeleteWithDependenciesException;
use App\Exceptions\InvalidPropertyAssignmentException;
use App\Enums\SubscriptionPlanType;
use App\Enums\UserAssignmentAction;
use App\Models\Property;
use App\Models\User;
use App\Notifications\TenantReassignedEmail;
use App\Notifications\WelcomeEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountManagementService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Create a new admin account with subscription.
     *
     * @param array $data Account data (email, password, name, organization_name, plan_type, expires_at)
     * @param User $superadmin The superadmin creating this account
     * @return User The created admin user
     * @throws ValidationException If validation fails
     */
    public function createAdminAccount(array $data, User $superadmin): User
    {
        // Validate input data
        $validator = Validator::make($data, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'plan_type' => ['required', Rule::in(SubscriptionPlanType::values())],
            'expires_at' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return DB::transaction(function () use ($data, $superadmin) {
            // Generate unique tenant_id
            $tenantId = $this->generateUniqueTenantId();

            // Create admin user
            $admin = User::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'organization_name' => $data['organization_name'],
                'is_active' => true,
            ]);

            // Create associated subscription
            $this->subscriptionService->createSubscription(
                $admin,
                $data['plan_type'],
                \Carbon\Carbon::parse($data['expires_at'])
            );

            // Log the action
            $this->logAuditAction($admin, null, null, UserAssignmentAction::CREATED, $superadmin);

            return $admin;
        });
    }

    /**
     * Create a new tenant account for a property.
     *
     * @param array $data Tenant data (email, password, name, property_id)
     * @param User $admin The admin creating this tenant
     * @return User The created tenant user
     * @throws ValidationException If validation fails
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     */
    public function createTenantAccount(array $data, User $admin): User
    {
        // Validate input data
        $validator = Validator::make($data, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'property_id' => 'required|exists:properties,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate property ownership
        $property = Property::find($data['property_id']);
        
        if (!$property || $property->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant to property from different organization.'
            );
        }

        // Check subscription limits
        $this->subscriptionService->enforceSubscriptionLimits($admin, 'tenant');

        return DB::transaction(function () use ($data, $admin, $property) {
            // Create tenant user inheriting admin's tenant_id
            $tenant = User::create([
                'tenant_id' => $admin->tenant_id,
                'property_id' => $property->id,
                'parent_user_id' => $admin->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'tenant',
                'is_active' => true,
            ]);

            // Log the action
            $this->logAuditAction($tenant, $property->id, null, UserAssignmentAction::CREATED, $admin);

            // Queue welcome email notification
            $tenant->notify(new WelcomeEmail($property, $data['password']));

            return $tenant;
        });
    }

    /**
     * Assign a tenant to a property.
     *
     * @param User $tenant The tenant to assign
     * @param Property $property The property to assign to
     * @param User $admin The admin performing the assignment
     * @return void
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     */
    public function assignTenantToProperty(User $tenant, Property $property, User $admin): void
    {
        // Validate property ownership
        if ($property->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant to property from different organization.'
            );
        }

        DB::transaction(function () use ($tenant, $property, $admin) {
            $previousPropertyId = $tenant->property_id;

            // Update property assignment
            $tenant->update([
                'property_id' => $property->id,
            ]);

            // Create audit log entry
            $this->logAuditAction($tenant, $property->id, $previousPropertyId, UserAssignmentAction::ASSIGNED, $admin);

            // Queue notification email
            $tenant->notify(new TenantReassignedEmail($property, null));
        });
    }

    /**
     * Reassign a tenant to a different property.
     *
     * @param User $tenant The tenant to reassign
     * @param Property $newProperty The new property to assign to
     * @param User $admin The admin performing the reassignment
     * @return void
     * @throws InvalidPropertyAssignmentException If property doesn't belong to admin
     */
    public function reassignTenant(User $tenant, Property $newProperty, User $admin): void
    {
        // Validate property ownership
        if ($newProperty->tenant_id !== $admin->tenant_id) {
            throw new InvalidPropertyAssignmentException(
                'Cannot assign tenant to property from different organization.'
            );
        }

        DB::transaction(function () use ($tenant, $newProperty, $admin) {
            $previousPropertyId = $tenant->property_id;
            $previousProperty = $previousPropertyId ? Property::find($previousPropertyId) : null;

            // Update property assignment
            $tenant->update([
                'property_id' => $newProperty->id,
            ]);

            // Create audit log entry
            $this->logAuditAction($tenant, $newProperty->id, $previousPropertyId, UserAssignmentAction::REASSIGNED, $admin);

            // Queue notification email
            $tenant->notify(new TenantReassignedEmail($newProperty, $previousProperty));
        });
    }

    /**
     * Deactivate a user account.
     *
     * @param User $user The user to deactivate
     * @param string $reason The reason for deactivation
     * @return void
     */
    public function deactivateAccount(User $user, ?string $reason = null): void
    {
        DB::transaction(function () use ($user, $reason) {
            // Update is_active status
            $user->update([
                'is_active' => false,
            ]);

            // Create audit log entry
            $this->logAuditAction($user, $user->property_id, null, UserAssignmentAction::DEACTIVATED, auth()->user(), $reason);
        });
    }

    /**
     * Reactivate a user account.
     *
     * @param User $user The user to reactivate
     * @return void
     */
    public function reactivateAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Update is_active status
            $user->update([
                'is_active' => true,
            ]);

            // Create audit log entry
            $this->logAuditAction($user, $user->property_id, null, UserAssignmentAction::REACTIVATED, auth()->user());
        });
    }

    /**
     * Delete a user account with validation.
     *
     * @param User $user The user to delete
     * @return void
     * @throws CannotDeleteWithDependenciesException If user has dependencies
     */
    public function deleteAccount(User $user): void
    {
        // Check for dependencies (historical data)
        $hasMeterReadings = $user->meterReadings()->exists();
        $hasChildUsers = $user->childUsers()->exists();
        $hasAuditLogs = DB::table('user_assignments_audit')
            ->where('user_id', $user->id)
            ->exists();

        if ($hasMeterReadings || $hasChildUsers || $hasAuditLogs) {
            throw new CannotDeleteWithDependenciesException(
                'Cannot delete user because it has associated historical data. Please deactivate instead.'
            );
        }

        // If no dependencies, allow deletion
        $user->delete();
    }

    /**
     * Generate a unique tenant_id.
     *
     * @return int
     */
    protected function generateUniqueTenantId(): int
    {
        do {
            $tenantId = random_int(1000, 999999);
        } while (User::where('tenant_id', $tenantId)->exists());

        return $tenantId;
    }

    /**
     * Log an audit action for user account management.
     *
     * @param User $user The user being acted upon
     * @param int|null $propertyId The property ID (if applicable)
     * @param int|null $previousPropertyId The previous property ID (for reassignments)
     * @param UserAssignmentAction $action The action performed
     * @param User|null $performedBy The user performing the action
     * @param string|null $reason Optional reason for the action
     * @return void
     */
    protected function logAuditAction(
        User $user,
        ?int $propertyId,
        ?int $previousPropertyId,
        UserAssignmentAction $action,
        ?User $performedBy,
        ?string $reason = null
    ): void {
        DB::table('user_assignments_audit')->insert([
            'user_id' => $user->id,
            'property_id' => $propertyId,
            'previous_property_id' => $previousPropertyId,
            'performed_by' => $performedBy?->id,
            'action' => $action->value,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
