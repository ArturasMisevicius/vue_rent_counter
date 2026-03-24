<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'country_code',
        'created_at',
        'updated_at',
    ];

    private const CONTROL_PLANE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'country_code',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'country_code',
    ];

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithPropertyCount(Builder $query): Builder
    {
        return $query->withCount('properties');
    }

    public function scopeWithMeterCount(Builder $query): Builder
    {
        return $query->withCount('meters');
    }

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withPropertyCount()
            ->withMeterCount()
            ->ordered();
    }

    public function scopeForSuperadminControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::CONTROL_PLANE_COLUMNS)
            ->with('organization:id,name')
            ->withPropertyCount()
            ->withMeterCount()
            ->ordered();
    }

    public function getAddressAttribute(): string
    {
        return $this->formattedAddress();
    }

    public function formattedAddress(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->postal_code,
            $this->country_code,
        ])->filter(fn (?string $part): bool => filled($part))->implode(', ');
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        $propertiesCount = $this->getAttribute('properties_count');

        if (is_numeric($propertiesCount)) {
            return (int) $propertiesCount === 0;
        }

        return ! $this->properties()->exists();
    }

    public function adminDeletionBlockedReason(): ?string
    {
        return $this->canBeDeletedFromAdminWorkspace()
            ? null
            : __('admin.buildings.messages.delete_blocked');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function meters(): HasManyThrough
    {
        return $this->hasManyThrough(
            Meter::class,
            Property::class,
            'building_id',
            'property_id',
            'id',
            'id',
        );
    }
}
