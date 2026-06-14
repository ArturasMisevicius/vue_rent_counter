<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use Database\Factories\HelpArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticle extends Model
{
    /** @use HasFactory<HelpArticleFactory> */
    use HasFactory;

    public const SUMMARY_COLUMNS = [
        'id',
        'slug',
        'category',
        'title',
        'body',
        'locale',
        'role',
        'tags',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'slug',
        'category',
        'title',
        'body',
        'locale',
        'role',
        'tags',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category' => HelpArticleCategory::class,
            'role' => HelpAudienceRole::class,
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function contexts(): HasMany
    {
        return $this->hasMany(HelpContext::class, 'article_slug', 'slug');
    }

    public function scopeForHelpListing(Builder $query): Builder
    {
        return $query->select(self::SUMMARY_COLUMNS);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        $roles = HelpAudienceRole::visibleValuesForUser($user);

        if ($roles === []) {
            return $query->whereKey(0);
        }

        return $query->whereIn('role', $roles);
    }

    public function scopeForCategoryValue(Builder $query, ?string $category): Builder
    {
        if (blank($category)) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeForLocales(Builder $query, array $locales): Builder
    {
        return $query->whereIn('locale', array_values(array_filter(array_unique($locales))));
    }

    public function scopeMatchingSearch(Builder $query, ?string $search): Builder
    {
        $term = trim((string) $search);

        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(function (Builder $query) use ($like): void {
            $query
                ->where('title', 'like', $like)
                ->orWhere('body', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhere('tags', 'like', $like);
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id');
    }
}
