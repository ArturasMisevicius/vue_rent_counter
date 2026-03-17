<?php

namespace App\Models;

use Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'from_currency_id',
                'to_currency_id',
                'rate',
                'effective_date',
                'source',
                'is_active',
            ])
            ->where('is_active', true);
    }

    public function scopeBetweenCurrencies(Builder $query, int $fromCurrencyId, int $toCurrencyId): Builder
    {
        return $query
            ->where('from_currency_id', $fromCurrencyId)
            ->where('to_currency_id', $toCurrencyId);
    }

    public function scopeForDate(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query
            ->whereDate('effective_date', '<=', $date->format('Y-m-d'))
            ->orderByDesc('effective_date');
    }
}
