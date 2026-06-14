<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HelpAudienceRole;
use Database\Factories\HelpContextFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpContext extends Model
{
    /** @use HasFactory<HelpContextFactory> */
    use HasFactory;

    public const SUMMARY_COLUMNS = [
        'id',
        'page_key',
        'article_slug',
        'role',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'page_key',
        'article_slug',
        'role',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'role' => HelpAudienceRole::class,
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'article_slug', 'slug');
    }

    public function scopeForHelpListing(Builder $query): Builder
    {
        return $query->select(self::SUMMARY_COLUMNS);
    }

    public function scopeForPage(Builder $query, string $pageKey): Builder
    {
        return $query->where('page_key', $pageKey);
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        $roles = HelpAudienceRole::visibleValuesForUser($user);

        if ($roles === []) {
            return $query->whereKey(0);
        }

        return $query->whereIn('role', $roles);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
