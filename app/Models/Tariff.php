<?php

namespace App\Models;

use Database\Factories\TariffFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    /** @use HasFactory<TariffFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'remote_id',
        'name',
        'configuration',
        'active_from',
        'active_until',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function serviceConfigurations(): HasMany
    {
        return $this->hasMany(ServiceConfiguration::class);
    }

    public function scopeActive(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $activeOn = $date ?? now();

        return $query
            ->select([
                'id',
                'provider_id',
                'remote_id',
                'name',
                'configuration',
                'active_from',
                'active_until',
                'created_at',
                'updated_at',
            ])
            ->where('active_from', '<=', $activeOn)
            ->where(function (Builder $builder) use ($activeOn): void {
                $builder
                    ->whereNull('active_until')
                    ->orWhere('active_until', '>=', $activeOn);
            });
    }
}
