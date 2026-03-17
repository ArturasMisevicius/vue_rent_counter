<?php

namespace App\Filament\Pages;

use App\Actions\Superadmin\Translations\ExportMissingTranslationsAction;
use App\Actions\Superadmin\Translations\ImportTranslationsAction;
use App\Actions\Superadmin\Translations\UpdateTranslationValueAction;
use App\Support\Superadmin\Translations\TranslationCatalogService;
use App\Support\Superadmin\Translations\TranslationRowData;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class TranslationManagement extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static ?string $navigationLabel = 'Translation Management';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected string $view = 'filament.pages.translation-management';

    public string $selectedLocale = '';

    public string $selectedGroup = '';

    public string $exportedMissingTranslationsPath = '';

    /**
     * @var array<string, string>
     */
    public array $translationValues = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public function mount(TranslationCatalogService $translationCatalogService): void
    {
        $this->selectedLocale = $translationCatalogService->initialEditableLocale();
        $this->selectedGroup = $translationCatalogService->groups()[0] ?? '';

        $this->loadCatalog();
    }

    public function updatedSelectedLocale(): void
    {
        $this->loadCatalog();
    }

    public function updatedSelectedGroup(): void
    {
        $this->loadCatalog();
    }

    public function loadCatalog(): void
    {
        $this->translationValues = $this->rows
            ->mapWithKeys(fn (TranslationRowData $row): array => [$row->stateKey => $row->translatedValue])
            ->all();
    }

    public function saveTranslationByStateKey(string $stateKey): void
    {
        $row = $this->rows->firstWhere('stateKey', $stateKey);

        if (! $row instanceof TranslationRowData) {
            return;
        }

        $this->updateTranslationValue(
            $row->key,
            $this->translationValues[$stateKey] ?? '',
        );
    }

    public function updateTranslationValue(string $key, string $value): void
    {
        if ($this->selectedLocale === '' || $this->selectedGroup === '') {
            return;
        }

        app(UpdateTranslationValueAction::class)(
            $this->selectedLocale,
            $this->selectedGroup,
            $key,
            $value,
        );

        $this->loadCatalog();

        Notification::make()
            ->title('Translation updated')
            ->success()
            ->send();
    }

    public function exportMissingTranslations(): void
    {
        if ($this->selectedLocale === '' || $this->selectedGroup === '') {
            return;
        }

        $this->exportedMissingTranslationsPath = app(ExportMissingTranslationsAction::class)(
            $this->selectedLocale,
            $this->selectedGroup,
        );

        Notification::make()
            ->title('Missing translations exported')
            ->success()
            ->send();
    }

    public function importTranslations(?string $path = null): void
    {
        if ($this->selectedLocale === '' || $this->selectedGroup === '') {
            return;
        }

        $importPath = $path ?? $this->exportedMissingTranslationsPath;

        if ($importPath === '') {
            return;
        }

        app(ImportTranslationsAction::class)(
            $this->selectedLocale,
            $this->selectedGroup,
            $importPath,
        );

        $this->loadCatalog();

        Notification::make()
            ->title('Translations imported')
            ->success()
            ->send();
    }

    /**
     * @return Collection<int, array{code: string, label: string, is_default: bool}>
     */
    public function getLocalesProperty(): Collection
    {
        return app(TranslationCatalogService::class)->activeLocales();
    }

    /**
     * @return array<int, string>
     */
    public function getGroupsProperty(): array
    {
        return app(TranslationCatalogService::class)->groups();
    }

    /**
     * @return Collection<int, TranslationRowData>
     */
    public function getRowsProperty(): Collection
    {
        if ($this->selectedLocale === '' || $this->selectedGroup === '') {
            return collect();
        }

        return app(TranslationCatalogService::class)->rows(
            $this->selectedLocale,
            $this->selectedGroup,
        );
    }
}
