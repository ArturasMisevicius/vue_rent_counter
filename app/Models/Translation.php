<?php

namespace App\Models;

use App\Services\TranslationPublisher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'values',
    ];

    protected $casts = [
        'values' => 'array',
    ];

    /**
     * Get all distinct translation groups.
     *
     * PERFORMANCE: Cached for 15 minutes to reduce database queries.
     * Cache is invalidated when translations are created, updated, or deleted.
     *
     * @return array<string, string>
     */
    public static function getDistinctGroups(): array
    {
        return cache()->remember('translations.groups', 900, function () {
            return static::query()
                ->distinct()
                ->orderBy('group')
                ->pluck('group', 'group')
                ->toArray();
        });
    }

    protected static function booted(): void
    {
        static::saved(function () {
            app(TranslationPublisher::class)->publish();
            // PERFORMANCE: Invalidate groups cache when translations change
            cache()->forget('translations.groups');
        });

        static::deleted(function () {
            app(TranslationPublisher::class)->publish();
            // PERFORMANCE: Invalidate groups cache when translations change
            cache()->forget('translations.groups');
        });
    }
}
