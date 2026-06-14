<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\HelpArticleCategory;
use App\Filament\Support\Help\HelpRepository;
use App\Models\HelpArticle;
use App\Models\User;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class HelpCenter extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'help';

    protected string $view = 'filament.pages.help-center';

    #[Url]
    public ?string $category = null;

    #[Url]
    public string $search = '';

    #[Url(as: 'page')]
    public ?string $pageKey = null;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        if ($this->category !== null && ! HelpArticleCategory::tryFrom($this->category) instanceof HelpArticleCategory) {
            $this->category = null;
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdminLike();
    }

    public function getTitle(): string
    {
        return __('help.pages.admin.title');
    }

    /**
     * @return array<int, array{title: string, body: string, slug: string, category: string, category_label: string}>
     */
    #[Computed]
    public function articles(): array
    {
        return app(HelpRepository::class)
            ->articlesFor($this->user(), $this->category, $this->search)
            ->map(fn (HelpArticle $article): array => $this->articleRow($article))
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, count: int}>
     */
    #[Computed]
    public function categories(): array
    {
        return app(HelpRepository::class)->categoriesFor($this->user());
    }

    /**
     * @return array<int, array{title: string, body: string, slug: string, category: string, category_label: string}>
     */
    #[Computed]
    public function contextArticles(): array
    {
        if (blank($this->pageKey)) {
            return [];
        }

        return app(HelpRepository::class)
            ->contextFor($this->user(), (string) $this->pageKey)
            ->map(fn (HelpArticle $article): array => $this->articleRow($article))
            ->all();
    }

    public function selectedCategoryLabel(): string
    {
        $category = is_string($this->category) ? HelpArticleCategory::tryFrom($this->category) : null;

        return $category instanceof HelpArticleCategory
            ? (string) $category->getLabel()
            : __('help.filters.all_categories');
    }

    /**
     * @return array{title: string, body: string, slug: string, category: string, category_label: string}
     */
    private function articleRow(HelpArticle $article): array
    {
        $category = $article->category instanceof HelpArticleCategory
            ? $article->category
            : HelpArticleCategory::tryFrom((string) $article->category);

        return [
            'title' => (string) $article->title,
            'body' => (string) $article->body,
            'slug' => (string) $article->slug,
            'category' => $category?->value ?? '',
            'category_label' => $category instanceof HelpArticleCategory ? (string) $category->getLabel() : '',
        ];
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
