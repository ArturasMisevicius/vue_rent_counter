<?php

namespace App\Models;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use Database\Factories\UtilityServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtilityService extends Model
{
    /** @use HasFactory<UtilityServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'unit_of_measurement',
        'default_pricing_model',
        'calculation_formula',
        'is_global_template',
        'created_by_organization_id',
        'configuration_schema',
        'validation_rules',
        'business_logic_config',
        'service_type_bridge',
        'description',
        'is_active',
    ];

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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdByOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'created_by_organization_id');
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'name',
                'slug',
                'unit_of_measurement',
                'default_pricing_model',
                'is_global_template',
                'created_by_organization_id',
                'service_type_bridge',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('is_active', true);
    }

    public function scopeForOrganization(Builder $query, ?int $organizationId): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'name',
                'slug',
                'unit_of_measurement',
                'default_pricing_model',
                'is_global_template',
                'created_by_organization_id',
                'service_type_bridge',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId);
    }

    public function scopeGlobalTemplates(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'name',
                'slug',
                'unit_of_measurement',
                'default_pricing_model',
                'is_global_template',
                'created_by_organization_id',
                'service_type_bridge',
                'description',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->where('is_global_template', true);
    }
}
