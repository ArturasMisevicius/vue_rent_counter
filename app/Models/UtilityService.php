<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Universal utility service configuration model.
 * Supports both global templates (SuperAdmin) and tenant customizations (Admin).
 */
class UtilityService extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'unit_of_measurement',
        'default_pricing_model',
        'calculation_formula',
        'is_global_template',
        'created_by_tenant_id',
        'configuration_schema',
        'validation_rules',
        'business_logic_config',
        'service_type_bridge',
        'description',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_pricing_model' => PricingModel::class,
            'calculation_formula' => 'array',
            'is_global_template' => 'boolean',
            'configuration_schema' => 'array',
            'validation_rules' => 'array',
            'business_logic_config' => 'array',
            'service_type_bridge' => ServiceType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the service configurations for this utility service.
     */
    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    /**
     * Get the properties that use this utility service through service configurations.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'service_configurations')
            ->withPivot(['pricing_model', 'rate_schedule', 'distribution_method', 'is_shared_service', 'effective_from', 'effective_until', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the tenant that created this service (for global templates).
     */
    public function createdByTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'created_by_tenant_id');
    }

    /**
     * Scope a query to global templates.
     */
    public function scopeGlobalTemplates($query)
    {
        return $query->where('is_global_template', true);
    }

    /**
     * Scope a query to tenant-specific services.
     */
    public function scopeTenantSpecific($query)
    {
        return $query->where('is_global_template', false);
    }

    /**
     * Scope a query to active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to services by pricing model.
     */
    public function scopeByPricingModel($query, PricingModel $pricingModel)
    {
        return $query->where('default_pricing_model', $pricingModel);
    }

    /**
     * Check if this service supports custom formulas.
     */
    public function supportsCustomFormulas(): bool
    {
        return $this->default_pricing_model->supportsCustomFormulas();
    }

    /**
     * Check if this service requires consumption data.
     */
    public function requiresConsumptionData(): bool
    {
        return $this->default_pricing_model->requiresConsumptionData();
    }

    /**
     * Get the validation schema for this service.
     */
    public function getValidationSchema(): array
    {
        return array_merge(
            $this->configuration_schema ?? [],
            $this->validation_rules ?? []
        );
    }

    /**
     * Validate configuration data against the service schema.
     */
    public function validateConfiguration(array $configuration): array
    {
        $schema = $this->getValidationSchema();
        $errors = [];

        // Basic validation - can be extended with JSON Schema validation
        foreach ($schema['required'] ?? [] as $field) {
            if (!isset($configuration[$field])) {
                $errors[] = "Required field '{$field}' is missing";
            }
        }

        return $errors;
    }

    /**
     * Get the bridge service type for backward compatibility.
     */
    public function getBridgeServiceType(): ?ServiceType
    {
        return $this->service_type_bridge;
    }

    /**
     * Create a tenant-specific copy of a global template.
     */
    public function createTenantCopy(int $tenantId, array $customizations = []): self
    {
        if (!$this->is_global_template) {
            throw new \InvalidArgumentException('Can only create tenant copies of global templates');
        }

        $copy = $this->replicate([
            'id',
            'created_at',
            'updated_at',
        ]);

        $copy->tenant_id = $tenantId;
        $copy->is_global_template = false;
        $copy->created_by_tenant_id = $this->created_by_tenant_id;

        // Apply customizations
        foreach ($customizations as $key => $value) {
            if ($key === 'slug') {
                continue;
            }

            if (in_array($key, $this->fillable)) {
                $copy->{$key} = $value;
            }
        }

        $slugSeed = Str::slug($customizations['slug'] ?? $customizations['name'] ?? $copy->slug ?? $copy->name ?? $this->slug ?? 'service');
        $slug = $slugSeed !== '' ? $slugSeed : 'service';
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $base = $slugSeed !== '' ? "{$slugSeed}-t{$tenantId}" : "service-t{$tenantId}";
            $suffix = $counter > 1 ? "-{$counter}" : '';
            $slug = "{$base}{$suffix}";
            $counter++;
        }

        $copy->slug = $slug;
        $copy->save();

        return $copy;
    }

    /**
     * Get cached service options for form selects.
     */
    public static function getCachedOptions(bool $globalOnly = false): \Illuminate\Support\Collection
    {
        $tenantId = TenantContext::id() ?? auth()->user()?->tenant_id;
        $cacheKey = $globalOnly
            ? 'utility_services.global_options'
            : 'utility_services.form_options.' . ($tenantId ?? 'no-tenant');
        
        return cache()->remember(
            $cacheKey,
            now()->addHour(),
            function () use ($globalOnly) {
                $query = static::query()
                    ->select('id', 'name', 'unit_of_measurement')
                    ->where('is_active', true)
                    ->orderBy('name');

                if ($globalOnly) {
                    $query->where('is_global_template', true);
                }

                return $query->get()->mapWithKeys(function ($service) {
                    return [$service->id => "{$service->name} ({$service->unit_of_measurement})"];
                });
            }
        );
    }

    /**
     * Clear cached options.
     */
    public static function clearCachedOptions(): void
    {
        $tenantId = TenantContext::id() ?? auth()->user()?->tenant_id;
        cache()->forget('utility_services.form_options.' . ($tenantId ?? 'no-tenant'));
        cache()->forget('utility_services.global_options');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('utility_service_visibility', function (Builder $query): void {
            $user = auth()->user();

            if (!$user) {
                if (app()->runningUnitTests() || app()->runningInConsole()) {
                    return;
                }

                $query->where('is_active', true)->where('is_global_template', true);
                return;
            }

            if ($user->role === UserRole::SUPERADMIN) {
                return;
            }

            $tenantId = TenantContext::id() ?? $user->tenant_id;

            $query->where(function (Builder $scoped) use ($tenantId): void {
                if ($tenantId !== null) {
                    $scoped->where('tenant_id', $tenantId);
                } else {
                    $scoped->whereRaw('1 = 0');
                }

                $scoped->orWhere('is_global_template', true);
            });
        });

        static::creating(function (self $service): void {
            if ($service->is_global_template) {
                $service->tenant_id = null;
                return;
            }

            if (!empty($service->tenant_id)) {
                return;
            }

            if (TenantContext::id() !== null) {
                $service->tenant_id = TenantContext::id();
                return;
            }

            if (auth()->check() && auth()->user()?->tenant_id) {
                $service->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Clear cache when services are modified
        static::created(fn () => static::clearCachedOptions());
        static::updated(fn () => static::clearCachedOptions());
        static::deleted(fn () => static::clearCachedOptions());
    }
}
