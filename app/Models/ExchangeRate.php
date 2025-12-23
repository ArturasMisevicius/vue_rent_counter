<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'effective_date',
        'source',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the base currency for this exchange rate.
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    /**
     * Get the target currency for this exchange rate.
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }

    /**
     * Scope to get only active exchange rates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rates for a specific date.
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('effective_date', '<=', $date->toDateString())
                    ->orderBy('effective_date', 'desc');
    }

    /**
     * Scope to get rates between specific currencies.
     */
    public function scopeBetweenCurrencies($query, int $fromCurrencyId, int $toCurrencyId)
    {
        return $query->where('from_currency_id', $fromCurrencyId)
                    ->where('to_currency_id', $toCurrencyId);
    }

    /**
     * Get the latest exchange rate between two currencies.
     */
    public static function getLatestRate(int $fromCurrencyId, int $toCurrencyId, ?Carbon $date = null): ?self
    {
        $date = $date ?? now();
        
        return static::active()
            ->betweenCurrencies($fromCurrencyId, $toCurrencyId)
            ->forDate($date)
            ->first();
    }

    /**
     * Convert an amount using this exchange rate.
     */
    public function convert(float $amount): float
    {
        return $amount * $this->rate;
    }

    /**
     * Get the inverse rate (for converting back).
     */
    public function getInverseRate(): float
    {
        return 1 / $this->rate;
    }

    /**
     * Check if this rate is current (effective today or earlier).
     */
    public function isCurrent(): bool
    {
        return $this->effective_date <= now()->toDateString();
    }

    /**
     * Get a human-readable description of this exchange rate.
     */
    public function getDescription(): string
    {
        return "1 {$this->fromCurrency->code} = {$this->rate} {$this->toCurrency->code}";
    }
}