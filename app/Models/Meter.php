<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use Database\Factories\MeterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meter extends Model
{
    /** @use HasFactory<MeterFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'name',
        'identifier',
        'type',
        'status',
        'unit',
        'installed_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'identifier',
        'type',
        'status',
        'unit',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MeterType::class,
            'status' => MeterStatus::class,
            'installed_at' => 'date',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MeterStatus::ACTIVE);
    }

    public function scopeOfType(Builder $query, MeterType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithPropertySummary(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
        ]);
    }

    public function scopeWithLatestReadingSummary(Builder $query): Builder
    {
        return $query->with([
            'latestReading:id,organization_id,meter_id,reading_value,reading_date,validation_status',
        ]);
    }

    public function scopeWithWorkspaceSummary(Builder $query): Builder
    {
        return $query
            ->withPropertySummary()
            ->withLatestReadingSummary();
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withWorkspaceSummary();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(MeterReading::class)
            ->whereNotNull('reading_date')
            ->latestFirst();
    }
}
