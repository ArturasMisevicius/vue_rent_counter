<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReadingAudit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'meter_reading_id',
        'changed_by_user_id',
        'old_value',
        'new_value',
        'change_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_value' => 'decimal:2',
            'new_value' => 'decimal:2',
        ];
    }

    /**
     * Get the meter reading this audit belongs to.
     */
    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    /**
     * Get the user who made this change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Alias for changedBy relationship for consistency.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->changedBy();
    }
}
