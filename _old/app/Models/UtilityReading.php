<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Utility reading model for tracking utility consumption.
 *
 * @property int $id
 * @property int $meter_id
 * @property float $value
 * @property Carbon $reading_date
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class UtilityReading extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'meter_id',
        'value',
        'reading_date',
        'notes',
    ];

    protected $casts = [
        'value' => 'float',
        'reading_date' => 'datetime',
    ];

    /**
     * Get the meter this reading belongs to.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }
}
