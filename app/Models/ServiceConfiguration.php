<?php

namespace App\Models;

use App\Enums\AssignmentScope;
use App\Enums\BillingFrequency;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceConfigurationStatus;
use App\Enums\ServiceType;
use Database\Factories\ServiceConfigurationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceConfiguration extends Model
{
    /** @use HasFactory<ServiceConfigurationFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'utility_service_id',
        'service_name',
        'service_type',
        'billing_method',
        'unit',
        'currency',
        'fixed_amount',
        'billing_frequency',
        'assignment_scope',
        'tenant_visible',
        'tenant_visible_name',
        'tenant_visible_description',
        'show_formula_to_tenant',
        'show_provider_to_tenant',
        'show_readings_to_tenant',
        'internal_note',
        'status',
        'starts_at',
        'ends_at',
        'meter_rules',
        'assignment_rules',
        'validation_result',
        'pricing_model',
        'rate_schedule',
        'distribution_method',
        'is_shared_service',
        'effective_from',
        'effective_until',
        'configuration_overrides',
        'tariff_id',
        'provider_id',
        'area_type',
        'custom_formula',
        'invoice_description',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'utility_service_id',
        'service_name',
        'service_type',
        'billing_method',
        'unit',
        'currency',
        'fixed_amount',
        'billing_frequency',
        'assignment_scope',
        'tenant_visible',
        'tenant_visible_name',
        'tenant_visible_description',
        'show_formula_to_tenant',
        'show_provider_to_tenant',
        'show_readings_to_tenant',
        'internal_note',
        'status',
        'starts_at',
        'ends_at',
        'meter_rules',
        'assignment_rules',
        'validation_result',
        'pricing_model',
        'rate_schedule',
        'distribution_method',
        'is_shared_service',
        'effective_from',
        'effective_until',
        'configuration_overrides',
        'tariff_id',
        'provider_id',
        'area_type',
        'custom_formula',
        'invoice_description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'billing_method' => BillingMethod::class,
            'fixed_amount' => 'decimal:2',
            'billing_frequency' => BillingFrequency::class,
            'assignment_scope' => AssignmentScope::class,
            'tenant_visible' => 'boolean',
            'show_formula_to_tenant' => 'boolean',
            'show_provider_to_tenant' => 'boolean',
            'show_readings_to_tenant' => 'boolean',
            'status' => ServiceConfigurationStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'meter_rules' => 'array',
            'assignment_rules' => 'array',
            'validation_result' => 'array',
            'pricing_model' => PricingModel::class,
            'rate_schedule' => 'array',
            'distribution_method' => DistributionMethod::class,
            'is_shared_service' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'configuration_overrides' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function utilityService(): BelongsTo
    {
        return $this->belongsTo(UtilityService::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->where('is_active', true)
            ->whereIn('status', [
                ServiceConfigurationStatus::ACTIVE->value,
                ServiceConfigurationStatus::CONFIGURATION_ERROR->value,
            ]);
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('effective_from')
            ->orderByDesc('id');
    }

    public function scopeWithPricingRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'utilityService:id,organization_id,name,unit_of_measurement,default_pricing_model,service_type_bridge',
            'provider:id,organization_id,name,service_type',
            'tariff:id,provider_id,name,configuration',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->withPricingRelations();

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->ordered();

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForPropertyValue(Builder $query, int|string|null $propertyId): Builder
    {
        if (blank($propertyId)) {
            return $query;
        }

        return $query->where('property_id', $propertyId);
    }

    public function scopeForActiveValue(Builder $query, bool|int|string|null $isActive): Builder
    {
        if ($isActive === null || $isActive === '') {
            return $query;
        }

        return $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false);
    }

    public function scopeEffectiveOn(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $effectiveOn = $date ?? now();

        return $query
            ->where('effective_from', '<=', $effectiveOn)
            ->where(function (Builder $builder) use ($effectiveOn): void {
                $builder
                    ->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $effectiveOn);
            });
    }

    public function scopeActiveOn(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        return $query
            ->active()
            ->effectiveOn($date);
    }

    public function requiresAreaData(): bool
    {
        return $this->distribution_method?->requiresAreaData() ?? false;
    }

    public function requiresConsumptionData(): bool
    {
        return ($this->billing_method?->requiresMeterRules() ?? false)
            || ($this->pricing_model?->requiresConsumptionData() ?? false)
            || ($this->distribution_method?->requiresConsumptionData() ?? false);
    }

    public function hasConfigurationErrors(): bool
    {
        if ($this->status === ServiceConfigurationStatus::CONFIGURATION_ERROR) {
            return true;
        }

        $blockingErrors = $this->validation_result['blocking_errors'] ?? [];

        return is_array($blockingErrors) && $blockingErrors !== [];
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        return ! $this->invoiceItems()
            ->select(['id', 'service_configuration_id'])
            ->exists();
    }

    public function adminDeletionBlockedReason(): ?string
    {
        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.service_configurations.messages.delete_blocked_used');
    }
}
