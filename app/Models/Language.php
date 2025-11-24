<?php

declare(strict_types=1);

namespace App\Models;

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
}
