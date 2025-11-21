<?php

namespace App\Models;

use App\Scopes\HierarchicalScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use HasFactory;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new HierarchicalScope);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
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
        return $this->hasMany(Property::class);
    }

    /**
     * Calculate the summer average gyvatukas for this building.
     * 
     * This method calculates the average circulation energy across the summer months
     * (May-September) and stores it for use during the heating season.
     *
     * @param Carbon $startDate Start of summer period (typically May 1)
     * @param Carbon $endDate End of summer period (typically September 30)
     * @return float Average circulation energy in kWh
     */
    public function calculateSummerAverage(Carbon $startDate, Carbon $endDate): float
    {
        $calculator = app(\App\Services\GyvatukasCalculator::class);
        
        $totalCirculation = 0.0;
        $monthCount = 0;
        
        // Iterate through each month in the summer period
        $currentMonth = $startDate->copy()->startOfMonth();
        
        while ($currentMonth->lte($endDate)) {
            // Only calculate for non-heating season months
            if (!$calculator->isHeatingSeason($currentMonth)) {
                $totalCirculation += $calculator->calculateSummerGyvatukas($this, $currentMonth);
                $monthCount++;
            }
            
            $currentMonth->addMonth();
        }
        
        // Calculate average
        $average = $monthCount > 0 ? $totalCirculation / $monthCount : 0.0;
        
        // Store the calculated average
        $this->gyvatukas_summer_average = $average;
        $this->gyvatukas_last_calculated = now();
        $this->save();
        
        return $average;
    }
}
