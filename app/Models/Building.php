<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Building extends Model
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
        'address',
        'total_apartments',
    ];

    /**
     * Get the properties in this building.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(\App\Models\Property::class);
    }

    /**
     * Get a friendly display name for the building (falls back to address).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->address ?: 'Building #' . $this->id;
    }

    /**
     * Get projects for this building (polymorphic)
     */
    public function projects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable');
    }

    /**
     * Get active projects for this building
     */
    public function activeProjects(): MorphMany
    {
        return $this->projects()->where('status', 'active');
    }
}
