<?php

namespace App\Models;

use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'slug',
        'name',
        'native_name',
        'name_translations',
        'region',
        'currency_code',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name_translations' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeBaltic(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'code',
                'slug',
                'name',
                'native_name',
                'name_translations',
                'region',
                'currency_code',
                'timezone',
                'is_active',
            ])
            ->where('region', 'baltics')
            ->where('is_active', true)
            ->orderBy('name');
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function translatedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name_translations[$locale]
            ?? $this->name_translations[config('app.fallback_locale')]
            ?? $this->name;
    }
}
