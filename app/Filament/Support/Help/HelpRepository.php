<?php

declare(strict_types=1);

namespace App\Filament\Support\Help;

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use App\Models\HelpArticle;
use App\Models\HelpContext;
use App\Models\User;
use Illuminate\Support\Collection;

final class HelpRepository
{
    public function __construct(
        private readonly DefaultHelpCatalog $defaultHelpCatalog,
    ) {}

    /**
     * @return Collection<int, HelpArticle>
     */
    public function articlesFor(User $user, ?string $category = null, ?string $search = null): Collection
    {
        $defaults = $this->defaultHelpCatalog->articlesFor($user, $category, $search);
        $databaseArticles = $this->databaseArticlesFor($user, $category, $search);

        $merged = $defaults->keyBy(fn (HelpArticle $article): string => (string) $article->slug);

        $databaseArticles->each(
            fn (HelpArticle $article): Collection => $merged->put((string) $article->slug, $article),
        );

        return $merged
            ->values()
            ->sortBy(fn (HelpArticle $article): string => $this->sortKey($article))
            ->values();
    }

    public function articleFor(User $user, string $slug): ?HelpArticle
    {
        return $this->articlesFor($user)
            ->first(fn (HelpArticle $article): bool => $article->slug === $slug);
    }

    /**
     * @return Collection<int, HelpArticle>
     */
    public function contextFor(User $user, string $pageKey): Collection
    {
        $databaseContexts = HelpContext::query()
            ->forHelpListing()
            ->forPage($pageKey)
            ->visibleToUser($user)
            ->ordered()
            ->get();

        $contexts = $this->defaultHelpCatalog
            ->contextsFor($user, $pageKey)
            ->concat($databaseContexts)
            ->sortBy([
                ['sort_order', 'asc'],
                ['article_slug', 'asc'],
            ])
            ->unique(fn (HelpContext $context): string => (string) $context->article_slug)
            ->values();

        if ($contexts->isEmpty()) {
            return collect();
        }

        $articles = $this->articlesFor($user)->keyBy(fn (HelpArticle $article): string => (string) $article->slug);

        return $contexts
            ->map(fn (HelpContext $context): ?HelpArticle => $articles->get((string) $context->article_slug))
            ->filter()
            ->values();
    }

    /**
     * @return array<int, array{value: string, label: string, count: int}>
     */
    public function categoriesFor(User $user): array
    {
        $counts = $this->articlesFor($user)
            ->groupBy(fn (HelpArticle $article): string => $this->categoryValue($article))
            ->map(fn (Collection $articles): int => $articles->count());

        return collect(HelpArticleCategory::cases())
            ->map(fn (HelpArticleCategory $category): array => [
                'value' => $category->value,
                'label' => (string) $category->getLabel(),
                'count' => (int) ($counts[$category->value] ?? 0),
            ])
            ->filter(fn (array $category): bool => $category['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, HelpArticle>
     */
    private function databaseArticlesFor(User $user, ?string $category, ?string $search): Collection
    {
        $preferredLocale = $this->preferredLocale($user);
        $locales = array_values(array_unique([$preferredLocale, 'en']));

        $articles = HelpArticle::query()
            ->forHelpListing()
            ->active()
            ->visibleToUser($user)
            ->forLocales($locales)
            ->forCategoryValue($category)
            ->matchingSearch($search)
            ->ordered()
            ->get();

        return $articles
            ->groupBy(fn (HelpArticle $article): string => (string) $article->slug)
            ->map(fn (Collection $variants): ?HelpArticle => $this->preferredVariant($variants, $preferredLocale))
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<int, HelpArticle>  $variants
     */
    private function preferredVariant(Collection $variants, string $preferredLocale): ?HelpArticle
    {
        return $variants
            ->sortBy(fn (HelpArticle $article): string => implode(':', [
                $article->locale === $preferredLocale ? '0' : '1',
                $this->roleValue($article) === HelpAudienceRole::ALL->value ? '1' : '0',
                str_pad((string) $article->sort_order, 5, '0', STR_PAD_LEFT),
            ]))
            ->first();
    }

    private function preferredLocale(User $user): string
    {
        $locale = trim((string) $user->locale);

        return $locale !== '' ? $locale : app()->getLocale();
    }

    private function sortKey(HelpArticle $article): string
    {
        return implode(':', [
            str_pad((string) $article->sort_order, 5, '0', STR_PAD_LEFT),
            $this->categoryValue($article),
            (string) $article->title,
        ]);
    }

    private function categoryValue(HelpArticle $article): string
    {
        return $article->category instanceof HelpArticleCategory
            ? $article->category->value
            : (string) $article->category;
    }

    private function roleValue(HelpArticle $article): string
    {
        return $article->role instanceof HelpAudienceRole
            ? $article->role->value
            : (string) $article->role;
    }
}
