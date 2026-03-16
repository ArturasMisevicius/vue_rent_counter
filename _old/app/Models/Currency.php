<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the exchange rates where this currency is the base currency.
     */
    public function fromExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'from_currency_id');
    }

    /**
     * Get the exchange rates where this currency is the target currency.
     */
    public function toExchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'to_currency_id');
    }

    /**
     * Get properties using this currency.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get invoices using this currency.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get invoices where this is the original currency.
     */
    public function originalInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'original_currency_id');
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get the default currency.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default currency.
     */
    public static function getDefault(): ?self
    {
        return static::default()->first();
    }

    /**
     * Format an amount according to this currency's decimal places.
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, $this->decimal_places, '.', ',');
    }

    /**
     * Get the display format for this currency.
     */
    public function getDisplayFormat(): string
    {
        return "{$this->symbol} {$this->code}";
    }

    /**
     * Check if this is the default currency.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if this currency is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}