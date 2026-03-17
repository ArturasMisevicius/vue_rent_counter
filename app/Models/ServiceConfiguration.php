<?php

namespace App\Models;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use Database\Factories\ServiceConfigurationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceConfiguration extends Model
{
    /** @use HasFactory<ServiceConfigurationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'utility_service_id',
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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'property_id',
                'utility_service_id',
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
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('is_active', true);
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

    public function requiresAreaData(): bool
    {
        return $this->distribution_method?->requiresAreaData() ?? false;
    }

    public function requiresConsumptionData(): bool
    {
        return ($this->pricing_model?->requiresConsumptionData() ?? false)
            || ($this->distribution_method?->requiresConsumptionData() ?? false);
    }
}
