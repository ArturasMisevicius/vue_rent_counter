<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'address_line_1',
        'address_line_2',
        'city',
        'postal_code',
        'country_code',
    ];

    public function scopeForOrganizationWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select([
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
            ])
            ->where('organization_id', $organizationId)
            ->withCount('properties');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }
}
