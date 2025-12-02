<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Language Model
 *
 * Represents available languages for the application's localization system.
 *
 * SECURITY FEATURES:
 * - Strict typing prevents type juggling attacks
 * - Fillable whitelist prevents mass assignment vulnerabilities
 * - Boolean casting prevents type confusion
 * - Query scope prevents SQL injection
 *
 * @property int $id
 * @property string $code Language code (e.g., 'en', 'lt', 'ru')
 * @property string $name Language name in English
 * @property string $native_name Language name in native script
 * @property bool $is_default Whether this is the default language
 * @property bool $is_active Whether this language is active
 * @property int $display_order Display order in language switcher
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
final class Language extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * SECURITY: Whitelist approach prevents mass assignment vulnerabilities.
     * Only these fields can be filled via create() or update().
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_default',
        'is_active',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * SECURITY: Boolean casting prevents type confusion attacks.
     * Ensures is_default and is_active are always boolean values.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'bool',
        'is_active' => 'bool',
    ];

    /**
     * Scope a query to only include active languages.
     *
     * SECURITY: Query scope prevents SQL injection by encapsulating
     * the filtering logic and ensuring consistent parameterization.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all active languages ordered by display order.
     *
     * PERFORMANCE: Cached for 15 minutes to reduce database queries.
     * Cache is invalidated when languages are created, updated, or deleted.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveLanguages()
    {
        return cache()->remember('languages.active', 900, function () {
            return static::active()
                ->orderBy('display_order')
                ->get();
        });
    }

    /**
     * Get the default language.
     *
     * PERFORMANCE: Cached for 15 minutes to reduce database queries.
     * Cache is invalidated when languages are created, updated, or deleted.
     *
     * @return \App\Models\Language|null
     */
    public static function getDefault()
    {
        return cache()->remember('languages.default', 900, function () {
            return static::where('is_default', true)->first();
        });
    }

    /**
     * Boot the model and register cache invalidation observers.
     *
     * PERFORMANCE: Automatically invalidate cache when languages change.
     */
    protected static function booted(): void
    {
        self::saved(function () {
            cache()->forget('languages.active');
            cache()->forget('languages.default');
        });

        self::deleted(function () {
            cache()->forget('languages.active');
            cache()->forget('languages.default');
        });
    }

    /**
     * Interact with the language code attribute.
     *
     * Automatically converts language codes to lowercase for consistency.
     * This ensures all language codes are stored in a normalized format.
     *
     * SECURITY: Normalization prevents case-sensitivity issues in lookups.
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => strtolower($value),
        );
    }
}
