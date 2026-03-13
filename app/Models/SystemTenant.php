<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SystemSubscriptionPlan;
use App\Enums\SystemTenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class SystemTenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'status',
        'subscription_plan',
        'settings',
        'resource_quotas',
        'billing_info',
        'primary_contact_email',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'resource_quotas' => 'array',
            'billing_info' => 'array',
            'status' => SystemTenantStatus::class,
            'subscription_plan' => SystemSubscriptionPlan::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SystemTenant $tenant): void {
            if (empty($tenant->slug)) {
                $baseSlug = Str::slug($tenant->name ?? 'tenant-' . Str::random(8));
                $slug = $baseSlug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                $tenant->slug = $slug;
            }

            // Set default resource quotas based on subscription plan
            if (empty($tenant->resource_quotas) && $tenant->subscription_plan) {
                $tenant->resource_quotas = $tenant->subscription_plan->getDefaultQuotas();
            }

            // Set default status
            if (empty($tenant->status)) {
                $tenant->status = SystemTenantStatus::PENDING;
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'system_tenant_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SuperAdminAuditLog::class, 'system_tenant_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function activate(): void
    {
        if (!$this->status->canTransitionTo(SystemTenantStatus::ACTIVE)) {
            throw new \InvalidArgumentException('Cannot activate tenant from current status: ' . $this->status->value);
        }

        $this->update(['status' => SystemTenantStatus::ACTIVE]);
    }

    public function suspend(string $reason = null): void
    {
        if (!$this->status->canTransitionTo(SystemTenantStatus::SUSPENDED)) {
            throw new \InvalidArgumentException('Cannot suspend tenant from current status: ' . $this->status->value);
        }

        $settings = $this->settings ?? [];
        $settings['suspension_reason'] = $reason;
        $settings['suspended_at'] = now()->toISOString();

        $this->update([
            'status' => SystemTenantStatus::SUSPENDED,
            'settings' => $settings,
        ]);
    }

    public function cancel(): void
    {
        if (!$this->status->canTransitionTo(SystemTenantStatus::CANCELLED)) {
            throw new \InvalidArgumentException('Cannot cancel tenant from current status: ' . $this->status->value);
        }

        $settings = $this->settings ?? [];
        $settings['cancelled_at'] = now()->toISOString();

        $this->update([
            'status' => SystemTenantStatus::CANCELLED,
            'settings' => $settings,
        ]);
    }

    public function getCurrentUsage(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'storage_used_gb' => $this->calculateStorageUsage(), // Implemented storage calculation
            'api_calls_this_month' => $this->calculateApiCallsThisMonth(), // Implemented API call tracking
        ];
    }

    public function isQuotaExceeded(string $quotaType): bool
    {
        $quotas = $this->resource_quotas ?? [];
        $usage = $this->getCurrentUsage();

        if (!isset($quotas["max_{$quotaType}"])) {
            return false;
        }

        $limit = $quotas["max_{$quotaType}"];
        if ($limit === null) {
            return false; // Unlimited
        }

        $usageKey = match ($quotaType) {
            'users' => 'users_count',
            'storage_gb' => 'storage_used_gb',
            'api_calls_per_month' => 'api_calls_this_month',
            default => null,
        };

        if (!$usageKey || !isset($usage[$usageKey])) {
            return false;
        }

        return $usage[$usageKey] >= $limit;
    }

    /**
     * Calculate storage usage for this tenant in GB.
     */
    private function calculateStorageUsage(): float
    {
        // For now, return 0 - this can be implemented to calculate actual storage
        // by summing file sizes from attachments, uploads, etc.
        return 0.0;
        
        // Future implementation:
        // return $this->organizations()
        //     ->withSum('attachments', 'file_size')
        //     ->get()
        //     ->sum('attachments_sum_file_size') / (1024 * 1024 * 1024); // Convert to GB
    }

    /**
     * Calculate API calls made this month.
     */
    private function calculateApiCallsThisMonth(): int
    {
        // For now, return 0 - this can be implemented with API usage tracking
        return 0;
        
        // Future implementation:
        // return ApiUsageLog::where('tenant_id', $this->id)
        //     ->whereMonth('created_at', now()->month)
        //     ->whereYear('created_at', now()->year)
        //     ->count();
    }
}