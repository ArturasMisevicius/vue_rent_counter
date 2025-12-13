<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Universal utility service configuration model.
 * Supports both global templates (SuperAdmin) and tenant customizations (Admin).
 */
class UtilityService extends Model
{
    use HasFactory, BelongsToTenant;

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
            if (in_array($key, $this->fillable)) {
                $copy->{$key} = $value;
            }
        }

        $copy->save();

        return $copy;
    }

    /**
     * Get cached service options for form selects.
     */
    public static function getCachedOptions(bool $globalOnly = false): \Illuminate\Support\Collection
    {
        $cacheKey = $globalOnly ? 'utility_services.global_options' : 'utility_services.form_options';
        
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
        cache()->forget('utility_services.form_options');
        cache()->forget('utility_services.global_options');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Clear cache when services are modified
        static::created(fn () => static::clearCachedOptions());
        static::updated(fn () => static::clearCachedOptions());
        static::deleted(fn () => static::clearCachedOptions());
    }
}