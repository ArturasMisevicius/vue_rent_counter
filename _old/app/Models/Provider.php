<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'service_type',
        'contact_info',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_type' => ServiceType::class,
            'contact_info' => 'array',
        ];
    }

    /**
     * Get the tariffs for this provider.
     */
    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    /**
     * Get cached provider options for form selects.
     * Cache for 1 hour to reduce database queries.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCachedOptions(): \Illuminate\Support\Collection
    {
        return cache()->remember(
            'providers.form_options',
            now()->addHour(),
            fn () => static::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->pluck('name', 'id')
        );
    }

    /**
     * Clear the cached provider options.
     * Call this after creating, updating, or deleting providers.
     *
     * @return void
     */
    public static function clearCachedOptions(): void
    {
        cache()->forget('providers.form_options');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Clear cache when providers are modified
        static::created(fn () => static::clearCachedOptions());
        static::updated(fn () => static::clearCachedOptions());
        static::deleted(fn () => static::clearCachedOptions());
    }
}
