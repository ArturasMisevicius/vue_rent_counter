<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'gyvatukas_summer_average',
        'gyvatukas_last_calculated',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gyvatukas_summer_average' => 'decimal:2',
            'gyvatukas_last_calculated' => 'date',
        ];
    }

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
     * Check if the building has a valid summer average.
     */
    public function hasSummerAverage(): bool
    {
        return $this->gyvatukas_summer_average !== null 
            && $this->gyvatukas_last_calculated !== null;
    }

    /**
     * Check if the summer average needs recalculation.
     */
    public function needsSummerAverageRecalculation(): bool
    {
        if (!$this->hasSummerAverage()) {
            return true;
        }

        $validityMonths = config('gyvatukas.summer_average_validity_months', 12);
        $cutoffDate = now()->subMonths($validityMonths);

        return $this->gyvatukas_last_calculated->isBefore($cutoffDate);
    }

    /**
     * Scope to buildings with valid summer averages.
     */
    public function scopeWithValidSummerAverage($query): void
    {
        $validityMonths = config('gyvatukas.summer_average_validity_months', 12);
        $cutoffDate = now()->subMonths($validityMonths);

        $query->whereNotNull('gyvatukas_summer_average')
              ->whereNotNull('gyvatukas_last_calculated')
              ->where('gyvatukas_last_calculated', '>=', $cutoffDate);
    }

    /**
     * Scope to buildings needing summer average recalculation.
     */
    public function scopeNeedingSummerAverageRecalculation($query): void
    {
        $validityMonths = config('gyvatukas.summer_average_validity_months', 12);
        $cutoffDate = now()->subMonths($validityMonths);

        $query->where(function ($q) use ($cutoffDate) {
            $q->whereNull('gyvatukas_summer_average')
              ->orWhereNull('gyvatukas_last_calculated')
              ->orWhere('gyvatukas_last_calculated', '<', $cutoffDate);
        });
    }

    /**
     * Scope for efficient gyvatukas calculations (select only needed columns).
     */
    public function scopeForGyvatukasCalculation($query): void
    {
        $query->select([
            'id',
            'tenant_id',
            'total_apartments',
            'gyvatukas_summer_average',
            'gyvatukas_last_calculated',
        ]);
    }
}