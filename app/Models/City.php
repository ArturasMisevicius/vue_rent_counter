<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $fillable = [
        'country_id',
        'slug',
        'name',
        'native_name',
        'name_translations',
        'timezone',
        'postal_code_pattern',
        'latitude',
        'longitude',
        'is_capital',
        'population',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
            'is_capital' => 'boolean',
            'population' => 'integer',
        ];
    }

    public function scopeForCountry(Builder $query, int $countryId): Builder
    {
        return $query
            ->select([
                'id',
                'country_id',
                'slug',
                'name',
                'native_name',
                'name_translations',
                'timezone',
                'postal_code_pattern',
                'latitude',
                'longitude',
                'is_capital',
                'population',
            ])
            ->where('country_id', $countryId)
            ->orderByDesc('is_capital')
            ->orderBy('name');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function translatedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name_translations[$locale]
            ?? $this->name_translations[config('app.fallback_locale')]
            ?? $this->name;
    }
}
