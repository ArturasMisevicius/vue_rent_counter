<?php

namespace App\Models;

use Database\Factories\MeterReadingAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReadingAudit extends Model
{
    /** @use HasFactory<MeterReadingAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'meter_reading_id',
        'changed_by_user_id',
        'old_value',
        'new_value',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'decimal:3',
            'new_value' => 'decimal:3',
        ];
    }

    public function meterReading(): BelongsTo
    {
        return $this->belongsTo(MeterReading::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
