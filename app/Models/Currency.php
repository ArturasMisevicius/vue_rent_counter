<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function fromExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    public function toExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'code',
                'name',
                'symbol',
                'decimal_places',
                'is_active',
                'is_default',
            ])
            ->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'code',
                'name',
                'symbol',
                'decimal_places',
                'is_active',
                'is_default',
            ])
            ->where('is_default', true);
    }
}
