<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gyvatukas Calculation Audit Model
 *
 * Stores audit trail for all gyvatukas (circulation fee) calculations.
 * Provides forensic capability for billing disputes and compliance.
 *
 * ## Audit Data
 * - Input parameters (building, month, method)
 * - Calculation results (circulation energy, distribution)
 * - User who performed calculation
 * - Timestamp of calculation
 * - Intermediate values for debugging
 *
 * ## Security Requirements
 * - Requirement 8.1: Audit trail for calculations
 * - Requirement 8.2: Store calculation context
 * - Requirement 7.2: Tenant-scoped data
 *
 * @property int $id
 * @property int $building_id
 * @property int $tenant_id
 * @property int $calculated_by_user_id
 * @property string $billing_month
 * @property string $season
 * @property float $circulation_energy
 * @property float $total_heating_energy
 * @property float $hot_water_volume
 * @property float $water_heating_energy
 * @property string $distribution_method
 * @property array $distribution_result
 * @property array $calculation_metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @package App\Models
 */
final class GyvatukasCalculationAudit extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'building_id',
        'tenant_id',
        'calculated_by_user_id',
        'billing_month',
        'season',
        'circulation_energy',
        'total_heating_energy',
        'hot_water_volume',
        'water_heating_energy',
        'distribution_method',
        'distribution_result',
        'calculation_metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'circulation_energy' => 'decimal:2',
            'total_heating_energy' => 'decimal:2',
            'hot_water_volume' => 'decimal:3',
            'water_heating_energy' => 'decimal:2',
            'distribution_result' => 'array',
            'calculation_metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the building this calculation was performed for.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the user who performed this calculation.
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by_user_id');
    }

    /**
     * Get the user who performed this calculation (alias).
     */
    public function calculatedByUser(): BelongsTo
    {
        return $this->calculatedBy();
    }

    /**
     * Scope query to specific building.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $buildingId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBuilding($query, int $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    /**
     * Scope query to specific month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('billing_month', $month);
    }

    /**
     * Scope query to specific season.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $season
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    /**
     * Check if calculation resulted in negative energy warning.
     *
     * @return bool
     */
    public function hasNegativeEnergyWarning(): bool
    {
        return isset($this->calculation_metadata['negative_energy_warning']) 
            && $this->calculation_metadata['negative_energy_warning'] === true;
    }

    /**
     * Check if calculation used missing summer average.
     *
     * @return bool
     */
    public function hasMissingSummerAverageWarning(): bool
    {
        return isset($this->calculation_metadata['missing_summer_average']) 
            && $this->calculation_metadata['missing_summer_average'] === true;
    }

    /**
     * Get calculation duration in milliseconds.
     *
     * @return float|null
     */
    public function getCalculationDuration(): ?float
    {
        return $this->calculation_metadata['duration_ms'] ?? null;
    }

    /**
     * Get query count for this calculation.
     *
     * @return int|null
     */
    public function getQueryCount(): ?int
    {
        return $this->calculation_metadata['query_count'] ?? null;
    }
}
