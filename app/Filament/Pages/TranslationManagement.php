<?php

namespace App\Filament\Pages;

use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;
use Filament\Pages\Page;

class TranslationManagement extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'translation-management';

    protected string $view = 'filament.pages.translation-management';

    public function getTitle(): string
    {
        return 'Translation Management';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getViewData(): array
    {
        return [
            'rows' => app(TranslationCatalogService::class)->rows(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
