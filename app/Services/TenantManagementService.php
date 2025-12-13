<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantManagementInterface;
use App\Data\Tenant\BulkOperationResult;
use App\Data\Tenant\CreateTenantData;
use App\Enums\AuditAction;
use App\Models\Organization;
use App\Models\SuperAdminAuditLog;
use App\ValueObjects\TenantMetrics;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final readonly class TenantManagementService implements TenantManagementInterface
{
    public function createTenant(CreateTenantData $data): Organization
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate unique constraints
                if (Organization::where('email', $data->email)->exists()) {
                    throw new \InvalidArgumentException("Tenant with email {$data->email} already exists");
                }

                if ($data->domain && Organization::where('domain', $data->domain)->exists()) {
                    throw new \InvalidArgumentException("Tenant with domain {$data->domain} already exists");
                }

                $tenant = Organization::create($data->toArray());

            // Log the creation
            SuperAdminAuditLog::create([
                'admin_id' => $data->createdByAdminId,
                'action' => AuditAction::TENANT_CREATED,
                'target_type' => Organization::class,
                'target_id' => $tenant->id,
                'tenant_id' => $tenant->id,
                'changes' => [
                    'name' => $tenant->name,
                    'plan' => $tenant->plan->value,
                    'email' => $tenant->email,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Send welcome email (async)
            $this->sendWelcomeEmail($tenant);

                Log::info('Tenant created successfully', [
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name,
                    'email' => $tenant->email,
                    'plan' => $tenant->plan->value,
                    'admin_id' => $data->createdByAdminId,
                ]);

                return $tenant;
            } catch (\Exception $e) {
                Log::error('Failed to create tenant', [
                    'name' => $data->name,
                    'email' => $data->email,
                    'admin_id' => $data->createdByAdminId,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                throw new \RuntimeException('Failed to create tenant: ' . $e->getMessage(), 0, $e);
            }
        });
    }

    public function updateTenantSettings(Organization $tenant, array $settings): void
    {
        try {
            $originalSettings = $tenant->settings ?? [];
            $tenant->updateSettings($settings);

            Log::info('Tenant settings updated', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'changes' => array_diff_assoc($settings, $originalSettings),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tenant settings', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'settings' => $settings,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to update tenant settings: ' . $e->getMessage(), 0, $e);
        }
    }

    public function suspendTenant(Organization $tenant, string $reason, int $adminId): void
    {
        try {
            if ($tenant->suspended_at) {
                Log::warning('Attempted to suspend already suspended tenant', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'admin_id' => $adminId,
                ]);
                return;
            }

            $tenant->suspendByAdmin($reason, $adminId);

            Log::warning('Tenant suspended', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'reason' => $reason,
                'admin_id' => $adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to suspend tenant', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'reason' => $reason,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to suspend tenant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function activateTenant(Organization $tenant, int $adminId): void
    {
        try {
            if (!$tenant->suspended_at && $tenant->is_active) {
                Log::info('Attempted to activate already active tenant', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'admin_id' => $adminId,
                ]);
                return;
            }

            $tenant->reactivateByAdmin($adminId);

            Log::info('Tenant reactivated', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'admin_id' => $adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to activate tenant', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to activate tenant: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteTenant(Organization $tenant, int $adminId): void
    {
        DB::transaction(function () use ($tenant, $adminId) {
            // Soft delete the tenant
            $tenant->update(['is_active' => false]);

            // Log the deletion
            SuperAdminAuditLog::create([
                'admin_id' => $adminId,
                'action' => AuditAction::TENANT_DELETED,
                'target_type' => Organization::class,
                'target_id' => $tenant->id,
                'tenant_id' => $tenant->id,
                'changes' => [
                    'deleted_at' => now()->toISOString(),
                    'name' => $tenant->name,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            Log::warning('Tenant deleted', [
                'tenant_id' => $tenant->id,
                'name' => $tenant->name,
                'admin_id' => $adminId,
            ]);
        });
    }

    public function getTenantMetrics(Organization $tenant): TenantMetrics
    {
        return $tenant->getMetrics();
    }

    public function bulkUpdateTenants(Collection $tenants, array $updates, int $adminId): BulkOperationResult
    {
        $startTime = microtime(true);
        $successful = 0;
        $failed = 0;
        $errors = [];
        $successfulIds = [];
        $failedIds = [];

        foreach ($tenants as $tenant) {
            try {
                DB::transaction(function () use ($tenant, $updates, $adminId) {
                    $tenant->update($updates);

                    // Log the bulk update
                    SuperAdminAuditLog::create([
                        'admin_id' => $adminId,
                        'action' => AuditAction::BULK_OPERATION,
                        'target_type' => Organization::class,
                        'target_id' => $tenant->id,
                        'tenant_id' => $tenant->id,
                        'changes' => $updates,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                });

                $successful++;
                $successfulIds[] = $tenant->id;
            } catch (\Exception $e) {
                $failed++;
                $failedIds[] = $tenant->id;
                $errors[] = "Tenant {$tenant->id}: {$e->getMessage()}";

                Log::error('Bulk tenant update failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'admin_id' => $adminId,
                ]);
            }
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        return BulkOperationResult::mixed(
            total: $tenants->count(),
            successful: $successful,
            failed: $failed,
            errors: $errors,
            successfulIds: $successfulIds,
            failedIds: $failedIds,
            executionTime: $executionTime,
        );
    }

    public function updateResourceQuotas(Organization $tenant, array $quotas, int $adminId): void
    {
        $originalQuotas = $tenant->resource_quotas ?? [];
        
        foreach ($quotas as $resource => $quota) {
            $tenant->setResourceQuota($resource, $quota);
        }

        // Log the quota changes
        SuperAdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => AuditAction::TENANT_UPDATED,
            'target_type' => Organization::class,
            'target_id' => $tenant->id,
            'tenant_id' => $tenant->id,
            'changes' => [
                'resource_quotas' => [
                    'old' => $originalQuotas,
                    'new' => $tenant->resource_quotas,
                ],
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('Tenant resource quotas updated', [
            'tenant_id' => $tenant->id,
            'quotas' => $quotas,
            'admin_id' => $adminId,
        ]);
    }

    public function checkResourceLimits(Organization $tenant): array
    {
        $limits = [];

        if ($tenant->isOverQuota('storage_mb')) {
            $limits[] = [
                'resource' => 'storage_mb',
                'current' => $tenant->storage_used_mb,
                'limit' => $tenant->getResourceQuota('storage_mb', 1000),
                'percentage' => ($tenant->storage_used_mb / $tenant->getResourceQuota('storage_mb', 1000)) * 100,
            ];
        }

        if ($tenant->isOverQuota('api_calls')) {
            $limits[] = [
                'resource' => 'api_calls',
                'current' => $tenant->api_calls_today,
                'limit' => $tenant->api_calls_quota,
                'percentage' => ($tenant->api_calls_today / $tenant->api_calls_quota) * 100,
            ];
        }

        if ($tenant->isOverQuota('users')) {
            $limits[] = [
                'resource' => 'users',
                'current' => $tenant->users()->count(),
                'limit' => $tenant->max_users,
                'percentage' => ($tenant->users()->count() / $tenant->max_users) * 100,
            ];
        }

        if ($tenant->isOverQuota('properties')) {
            $limits[] = [
                'resource' => 'properties',
                'current' => $tenant->properties()->count(),
                'limit' => $tenant->max_properties,
                'percentage' => ($tenant->properties()->count() / $tenant->max_properties) * 100,
            ];
        }

        return $limits;
    }

    public function getAllTenants(array $filters = [], string $sortBy = 'created_at', string $sortDirection = 'desc'): Collection
    {
        $query = Organization::query()
            ->with(['users', 'properties', 'createdByAdmin']);

        // Apply filters
        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->active(),
                'suspended' => $query->whereNotNull('suspended_at'),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        if (!empty($filters['plan'])) {
            $query->onPlan($filters['plan']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (!empty($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        return $query->orderBy($sortBy, $sortDirection)->get();
    }

    private function sendWelcomeEmail(Organization $tenant): void
    {
        // TODO: Implement welcome email sending
        // This would typically use a queued job
        Log::info('Welcome email queued for tenant', [
            'tenant_id' => $tenant->id,
            'email' => $tenant->email,
        ]);
    }
}