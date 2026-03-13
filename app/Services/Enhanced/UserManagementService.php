<?php

declare(strict_types=1);

namespace App\Services\Enhanced;

use App\Actions\CreateUserAction;
use App\Actions\AssignRoleAction;
use App\Actions\SendWelcomeEmailAction;
use App\DTOs\CreateUserDTO;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Organization;
use App\Services\ServiceResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Enhanced User Management Service
 * 
 * Orchestrates user lifecycle operations with comprehensive business logic:
 * - User creation with role assignment
 * - Account activation and deactivation
 * - Role management with authorization
 * - Organization membership management
 * - User profile updates with validation
 * - Bulk user operations
 * 
 * @package App\Services\Enhanced
 */
final class UserManagementService extends BaseService
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly AssignRoleAction $assignRoleAction,
        private readonly SendWelcomeEmailAction $sendWelcomeEmailAction
    ) {
        parent::__construct();
    }

    /**
     * Create a new user with comprehensive validation and setup.
     *
     * @param CreateUserDTO $dto User creation data
     * @param bool $sendWelcomeEmail Whether to send welcome email
     * @return ServiceResponse<User>
     */
    public function createUser(CreateUserDTO $dto, bool $sendWelcomeEmail = true): ServiceResponse
    {
        try {
            return $this->withMetrics('create_user', function () use ($dto, $sendWelcomeEmail) {
                return $this->executeInTransaction(function () use ($dto, $sendWelcomeEmail) {
                    // Authorization check
                    $this->authorize('create', User::class);

                    // Validate tenant context for non-superadmin users
                    if ($dto->role !== UserRole::SUPER_ADMIN) {
                        $this->validateTenantContext($dto->tenantId);
                    }

                    // Check for existing user with same email
                    if (User::where('email', $dto->email)->exists()) {
                        return $this->error('User with this email already exists');
                    }

                    // Create user
                    $user = $this->createUserAction->execute($dto->toArray());

                    // Assign role with proper authorization
                    $roleResult = $this->assignRoleAction->execute($user, $dto->role);
                    if (!$roleResult) {
                        throw new \RuntimeException('Failed to assign role to user');
                    }

                    // Set up user profile
                    $this->setupUserProfile($user, $dto);

                    // Send welcome email if requested
                    if ($sendWelcomeEmail) {
                        $this->sendWelcomeEmailAction->execute($user);
                    }

                    $this->log('info', 'User created successfully', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'role' => $dto->role->value,
                        'tenant_id' => $dto->tenantId,
                        'created_by' => auth()->id(),
                    ]);

                    return $this->success($user, 'User created successfully');
                });
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'create_user',
                'email' => $dto->email,
                'role' => $dto->role->value,
            ]);

            return $this->error('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update user profile with validation.
     *
     * @param User $user
     * @param array $data
     * @return ServiceResponse<User>
     */
    public function updateUserProfile(User $user, array $data): ServiceResponse
    {
        try {
            $this->authorize('update', $user);
            $this->validateTenantOwnership($user);

            return $this->withMetrics('update_user_profile', function () use ($user, $data) {
                return $this->executeInTransaction(function () use ($user, $data) {
                    // Validate and sanitize input data
                    $validatedData = $this->validateProfileData($data);

                    // Handle password update separately
                    if (isset($validatedData['password'])) {
                        $validatedData['password'] = Hash::make($validatedData['password']);
                    }

                    // Update user
                    $user->update($validatedData);

                    $this->log('info', 'User profile updated', [
                        'user_id' => $user->id,
                        'updated_fields' => array_keys($validatedData),
                        'updated_by' => auth()->id(),
                    ]);

                    return $this->success($user, 'User profile updated successfully');
                });
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'update_user_profile',
                'user_id' => $user->id,
            ]);

            return $this->error('Failed to update user profile: ' . $e->getMessage());
        }
    }

    /**
     * Change user role with authorization checks.
     *
     * @param User $user
     * @param UserRole $newRole
     * @return ServiceResponse<User>
     */
    public function changeUserRole(User $user, UserRole $newRole): ServiceResponse
    {
        try {
            $this->authorize('changeRole', $user);
            $this->validateTenantOwnership($user);

            // Prevent role escalation beyond current user's permissions
            if (!$this->canAssignRole($newRole)) {
                return $this->error('Insufficient permissions to assign this role');
            }

            return $this->executeInTransaction(function () use ($user, $newRole) {
                $oldRole = $user->role;

                // Assign new role
                $result = $this->assignRoleAction->execute($user, $newRole);
                if (!$result) {
                    throw new \RuntimeException('Failed to assign new role');
                }

                $this->log('info', 'User role changed', [
                    'user_id' => $user->id,
                    'old_role' => $oldRole->value,
                    'new_role' => $newRole->value,
                    'changed_by' => auth()->id(),
                ]);

                return $this->success($user, 'User role changed successfully');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'change_user_role',
                'user_id' => $user->id,
                'new_role' => $newRole->value,
            ]);

            return $this->error('Failed to change user role: ' . $e->getMessage());
        }
    }

    /**
     * Activate a user account.
     *
     * @param User $user
     * @return ServiceResponse<User>
     */
    public function activateUser(User $user): ServiceResponse
    {
        try {
            $this->authorize('activate', $user);
            $this->validateTenantOwnership($user);

            if ($user->is_active) {
                return $this->error('User is already active');
            }

            $user->update(['is_active' => true]);

            $this->log('info', 'User activated', [
                'user_id' => $user->id,
                'activated_by' => auth()->id(),
            ]);

            return $this->success($user, 'User activated successfully');
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'activate_user',
                'user_id' => $user->id,
            ]);

            return $this->error('Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a user account.
     *
     * @param User $user
     * @return ServiceResponse<User>
     */
    public function deactivateUser(User $user): ServiceResponse
    {
        try {
            $this->authorize('deactivate', $user);
            $this->validateTenantOwnership($user);

            if (!$user->is_active) {
                return $this->error('User is already inactive');
            }

            // Prevent deactivating the last admin
            if ($this->isLastAdmin($user)) {
                return $this->error('Cannot deactivate the last admin user');
            }

            $user->update(['is_active' => false]);

            $this->log('info', 'User deactivated', [
                'user_id' => $user->id,
                'deactivated_by' => auth()->id(),
            ]);

            return $this->success($user, 'User deactivated successfully');
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'deactivate_user',
                'user_id' => $user->id,
            ]);

            return $this->error('Failed to deactivate user: ' . $e->getMessage());
        }
    }

    /**
     * Get users for a tenant with filtering and pagination.
     *
     * @param int $tenantId
     * @param array $filters
     * @return ServiceResponse<Collection>
     */
    public function getTenantUsers(int $tenantId, array $filters = []): ServiceResponse
    {
        try {
            // Validate tenant access
            $this->validateTenantAccess($tenantId);

            $query = User::where('tenant_id', $tenantId)
                ->with(['organization']);

            // Apply filters
            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('name')->get();

            return $this->success($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'get_tenant_users',
                'tenant_id' => $tenantId,
            ]);

            return $this->error('Failed to retrieve users: ' . $e->getMessage());
        }
    }

    /**
     * Bulk create users from array data.
     *
     * @param array $usersData
     * @param bool $sendWelcomeEmails
     * @return ServiceResponse<array>
     */
    public function bulkCreateUsers(array $usersData, bool $sendWelcomeEmails = false): ServiceResponse
    {
        try {
            $this->authorize('create', User::class);

            return $this->withMetrics('bulk_create_users', function () use ($usersData, $sendWelcomeEmails) {
                $results = [
                    'successful' => [],
                    'failed' => [],
                    'total_processed' => 0,
                ];

                foreach ($usersData as $userData) {
                    $results['total_processed']++;

                    try {
                        $dto = CreateUserDTO::fromArray($userData);
                        $result = $this->createUser($dto, $sendWelcomeEmails);

                        if ($result->success) {
                            $results['successful'][] = [
                                'email' => $dto->email,
                                'user_id' => $result->data->id,
                            ];
                        } else {
                            $results['failed'][] = [
                                'email' => $dto->email,
                                'error' => $result->message,
                            ];
                        }
                    } catch (\Exception $e) {
                        $results['failed'][] = [
                            'email' => $userData['email'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                $this->log('info', 'Bulk user creation completed', [
                    'total_processed' => $results['total_processed'],
                    'successful_count' => count($results['successful']),
                    'failed_count' => count($results['failed']),
                ]);

                return $this->success($results, 'Bulk user creation completed');
            });
        } catch (\Exception $e) {
            $this->handleException($e, [
                'operation' => 'bulk_create_users',
                'user_count' => count($usersData),
            ]);

            return $this->error('Bulk user creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate tenant context for user operations.
     */
    private function validateTenantContext(int $tenantId): void
    {
        $currentUser = auth()->user();

        // SuperAdmin can work with any tenant
        if ($currentUser->role === UserRole::SUPER_ADMIN) {
            return;
        }

        // Other users can only work within their tenant
        if ($currentUser->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Cannot create user for different tenant');
        }
    }

    /**
     * Set up user profile with additional data.
     */
    private function setupUserProfile(User $user, CreateUserDTO $dto): void
    {
        // Set up organization membership if provided
        if ($dto->organizationName && $dto->tenantId) {
            $organization = Organization::firstOrCreate([
                'name' => $dto->organizationName,
                'tenant_id' => $dto->tenantId,
            ]);

            $user->update(['organization_id' => $organization->id]);
        }

        // Set up parent-child relationship if provided
        if ($dto->parentUserId) {
            $user->update(['parent_user_id' => $dto->parentUserId]);
        }
    }

    /**
     * Validate profile update data.
     */
    private function validateProfileData(array $data): array
    {
        $allowedFields = [
            'name', 'email', 'password', 'organization_name', 'is_active'
        ];

        $validated = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $validated[$key] = $value;
            }
        }

        // Additional validation rules would go here
        if (isset($validated['email'])) {
            if (!filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email format');
            }
        }

        if (isset($validated['password'])) {
            if (strlen($validated['password']) < 8) {
                throw new \InvalidArgumentException('Password must be at least 8 characters');
            }
        }

        return $validated;
    }

    /**
     * Check if current user can assign the specified role.
     */
    private function canAssignRole(UserRole $role): bool
    {
        $currentUser = auth()->user();

        // SuperAdmin can assign any role
        if ($currentUser->role === UserRole::SUPER_ADMIN) {
            return true;
        }

        // Admin can assign roles below their level
        if ($currentUser->role === UserRole::ADMIN) {
            return in_array($role, [UserRole::MANAGER, UserRole::TENANT]);
        }

        // Manager can only assign tenant role
        if ($currentUser->role === UserRole::MANAGER) {
            return $role === UserRole::TENANT;
        }

        return false;
    }

    /**
     * Check if user is the last admin in their tenant.
     */
    private function isLastAdmin(User $user): bool
    {
        if ($user->role !== UserRole::ADMIN) {
            return false;
        }

        $adminCount = User::where('tenant_id', $user->tenant_id)
            ->where('role', UserRole::ADMIN)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->count();

        return $adminCount === 0;
    }

    /**
     * Validate tenant access for current user.
     */
    private function validateTenantAccess(int $tenantId): void
    {
        $currentUser = auth()->user();

        if ($currentUser->role === UserRole::SUPER_ADMIN) {
            return; // SuperAdmin has access to all tenants
        }

        if ($currentUser->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Access denied to tenant data');
        }
    }
}