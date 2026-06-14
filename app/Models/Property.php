<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PropertyOccupancyStatus;
use App\Enums\PropertyType;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'building_id',
        'name',
        'floor',
        'unit_number',
        'type',
        'floor_area_sqm',
        'occupancy_status',
        'created_at',
        'updated_at',
    ];

    private const CONTROL_PLANE_COLUMNS = [
        'id',
        'organization_id',
        'building_id',
        'name',
        'floor',
        'unit_number',
        'type',
        'floor_area_sqm',
        'occupancy_status',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'building_id',
        'name',
        'floor',
        'unit_number',
        'type',
        'floor_area_sqm',
        'occupancy_status',
    ];

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'occupancy_status' => PropertyOccupancyStatus::class,
            'floor' => 'integer',
            'floor_area_sqm' => 'decimal:2',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeWithAddressRelations(Builder $query): Builder
    {
        return $query->with([
            'building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
        ]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithCurrentAssignmentSummary(Builder $query): Builder
    {
        return $query->with([
            'currentAssignment:id,organization_id,property_id,tenant_user_id,unit_area_sqm,status,is_primary,occupants_count,assigned_at,unassigned_at,move_out_date,billing_start_date,billing_end_date',
            'currentAssignment.tenant:id,name',
        ]);
    }

    public function scopeWithAssignmentHistory(Builder $query): Builder
    {
        return $query->with([
            'assignments:id,organization_id,property_id,tenant_user_id,unit_area_sqm,status,is_primary,occupants_count,assigned_at,unassigned_at,move_out_date,billing_start_date,billing_end_date',
            'assignments.tenant:id,name',
        ]);
    }

    public function scopeWithWorkspaceSummary(Builder $query): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withAddressRelations()
            ->withCurrentAssignmentSummary()
            ->withCount('meters');
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->forOrganization($organizationId)
            ->withWorkspaceSummary()
            ->withAssignmentHistory()
            ->ordered();
    }

    public function scopeForSuperadminControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::CONTROL_PLANE_COLUMNS)
            ->withWorkspaceSummary()
            ->withAssignmentHistory()
            ->with('organization:id,name')
            ->ordered();
    }

    public function scopeAvailableForTenantAssignment(
        Builder $query,
        int $organizationId,
        ?int $tenantId = null,
    ): Builder {
        return $query
            ->select([
                'id',
                'organization_id',
                'building_id',
                'name',
                'floor',
                'unit_number',
                'type',
                'floor_area_sqm',
                'occupancy_status',
            ])
            ->where('organization_id', $organizationId)
            ->with([
                'building:id,organization_id,name',
            ])
            ->where(function (Builder $propertyQuery) use ($tenantId): void {
                $propertyQuery
                    ->whereDoesntHave('currentAssignment')
                    ->when(
                        $tenantId !== null,
                        fn (Builder $tenantPropertyQuery): Builder => $tenantPropertyQuery->orWhereHas(
                            'currentAssignment',
                            fn (Builder $assignmentQuery): Builder => $assignmentQuery->where('tenant_user_id', $tenantId),
                        ),
                    );
            })
            ->ordered();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PropertyAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(PropertyAssignment::class)
            ->current()
            ->latestAssignedFirst();
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function meterReadings(): HasManyThrough
    {
        return $this->hasManyThrough(
            MeterReading::class,
            Meter::class,
            'property_id',
            'meter_id',
            'id',
            'id',
        );
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function extraCharges(): HasMany
    {
        return $this->hasMany(ExtraCharge::class);
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class);
    }

    public function moveOutProcesses(): HasMany
    {
        return $this->hasMany(MoveOutProcess::class);
    }

    public function getCurrentTenantAttribute(): ?User
    {
        return $this->currentAssignment?->tenant;
    }

    public function isOccupied(): bool
    {
        return $this->occupancyStatus() === PropertyOccupancyStatus::OCCUPIED;
    }

    public function occupancyStatusLabel(): string
    {
        return $this->occupancyStatus()->label();
    }

    public function occupancyStatus(): PropertyOccupancyStatus
    {
        if ($this->occupancy_status instanceof PropertyOccupancyStatus) {
            return $this->occupancy_status;
        }

        if (is_string($this->occupancy_status) && $this->occupancy_status !== '') {
            return PropertyOccupancyStatus::tryFrom($this->occupancy_status) ?? PropertyOccupancyStatus::VACANT;
        }

        return $this->currentAssignment !== null
            ? PropertyOccupancyStatus::OCCUPIED
            : PropertyOccupancyStatus::VACANT;
    }

    public function floorDisplay(): string
    {
        return match (true) {
            $this->floor === 0 => __('admin.properties.floor.ground'),
            $this->floor !== null => (string) $this->floor,
            default => '—',
        };
    }

    public function areaDisplay(): string
    {
        if ($this->floor_area_sqm === null) {
            return '—';
        }

        return $this->formatDecimal((float) $this->floor_area_sqm, 2).' m²';
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }

    public function displayName(): string
    {
        $name = app(DatabaseContentLocalizer::class)->propertyName($this->name, $this->type, $this->unit_number);

        return $name !== '' ? $name : __('dashboard.not_available');
    }

    public function tenantAssignmentLabel(): string
    {
        $parts = array_filter([
            $this->displayName(),
            $this->unit_number,
            $this->building?->displayName(),
        ]);

        return implode(' · ', $parts);
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        if ($this->currentAssignment !== null) {
            return false;
        }

        return ! (
            $this->assignments()->exists()
            || $this->meters()->exists()
            || $this->invoices()->exists()
            || $this->rentalContracts()->exists()
        );
    }

    public function adminDeletionBlockedReason(): ?string
    {
        if ($this->currentAssignment !== null) {
            return __('admin.properties.messages.delete_blocked_active_tenant');
        }

        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.properties.messages.delete_blocked');
    }

    public function getAddressAttribute(): string
    {
        $building = $this->building;
        $parts = array_filter([
            $building?->displayName(),
            $building?->address,
        ]);

        return implode(', ', $parts);
    }
}
