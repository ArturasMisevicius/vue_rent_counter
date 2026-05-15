<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
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
        return $query->where($query->qualifyColumn('organization_id'), $organizationId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where($query->qualifyColumn('property_id'), $propertyId);
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
        $table = $query->getModel()->getTable();

        return $query
            ->orderBy("{$table}.name")
            ->orderBy("{$table}.id");
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

    public function scopeWithOrganizationSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeWithWorkspaceSummary(Builder $query): Builder
    {
        return $query
            ->withPropertySummary()
            ->withLatestReadingSummary();
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->withWorkspaceSummary();

        if (! $includeOrganization) {
            return $query;
        }

        return $query->withOrganizationSummary();
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->ordered();

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->forOrganization($organizationId);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForBuildingValue(Builder $query, int|string|null $buildingId): Builder
    {
        if (blank($buildingId)) {
            return $query;
        }

        return $query->whereHas(
            'property',
            fn (Builder $propertyQuery): Builder => $propertyQuery->where('building_id', $buildingId),
        );
    }

    public function scopeForPropertyValue(Builder $query, int|string|null $propertyId): Builder
    {
        if (blank($propertyId)) {
            return $query;
        }

        return $query->where('property_id', $propertyId);
    }

    public function scopeForTypeValue(Builder $query, int|string|null $type): Builder
    {
        if (blank($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeForStatusValue(Builder $query, int|string|null $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
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

    public function displayName(): string
    {
        return app(DatabaseContentLocalizer::class)->meterName($this->name, $this->type);
    }
}
