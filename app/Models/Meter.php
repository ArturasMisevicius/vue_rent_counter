<?php

namespace App\Models;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use Database\Factories\MeterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meter extends Model
{
    /** @use HasFactory<MeterFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'identifier',
        'type',
        'status',
        'unit',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MeterType::class,
            'status' => MeterStatus::class,
            'installed_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(MeterReading::class)
            ->orderByDesc('reading_date')
            ->orderByDesc('id');
    }
}
