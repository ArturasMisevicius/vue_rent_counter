<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\TranslationResource\Pages;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Filament resource for managing translations.
 *
 * Provides CRUD operations for translation strings with:
 * - Superadmin-only access
 * - Multi-language value management
 * - Group and key organization
 * - PHP language file integration
 *
 * @see \App\Models\Translation
 */
class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.localization');
    }

    public static function getNavigationLabel(): string
    {
        return __('translations.navigation');
    }

    /**
     * Only superadmins can access translation management.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::SUPERADMIN;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::SUPERADMIN;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::SUPERADMIN;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::SUPERADMIN;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->role === UserRole::SUPERADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        // PERFORMANCE: Use cached active languages to avoid N+1 query on every form render
        $languages = Language::getActiveLanguages();

        return $schema
            ->schema([
                Forms\Components\Section::make(__('translations.sections.key'))
                    ->description(__('translations.helper_text.key'))
                    ->schema([
                        Forms\Components\TextInput::make('group')
                            ->required()
                            ->maxLength(120)
                            ->label(__('translations.labels.group'))
                            ->placeholder(__('translations.placeholders.group'))
                            ->helperText(__('translations.helper_text.group'))
                            ->alphaDash(),

                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->label(__('translations.labels.key'))
                            ->placeholder(__('translations.placeholders.key'))
                            ->helperText(__('translations.helper_text.key_full'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('translations.sections.values'))
                    ->description(__('translations.helper_text.values'))
                    ->schema(
                        $languages->map(function (Language $language) {
                            return Forms\Components\Textarea::make("values.{$language->code}")
                                ->label(__('translations.table.language_label', [
                                    'language' => $language->name,
                                    'code' => $language->code,
                                ]))
                                ->rows(3)
                                ->placeholder(__('translations.placeholders.value'))
                                ->helperText($language->is_default ? __('translations.helper_text.default_language') : '')
                                ->columnSpanFull();
                        })->all()
                    )
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // PERFORMANCE: Use cached default language to avoid query on every table render
        $defaultLocale = Language::getDefault()?->code ?? 'en';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label(__('translations.labels.group'))
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('key')
                    ->label(__('translations.labels.key'))
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('translations.labels.key'))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make("values->{$defaultLocale}")
                    ->label(__('translations.table.value_label', ['locale' => strtoupper($defaultLocale)]))
                    ->limit(50)
                    ->wrap()
                    ->placeholder(__('app.common.dash'))
                    ->tooltip(fn (?string $state): ?string => $state && strlen($state) > 50 ? $state : null),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('translations.labels.last_updated'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('translations.labels.group'))
                    // PERFORMANCE: Use cached distinct groups to avoid full table scan on every render
                    ->options(fn (): array => Translation::getDistinctGroups())
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('translations.modals.delete.heading'))
                        ->modalDescription(__('translations.modals.delete.description')),
                ]),
            ])
            ->emptyStateHeading(__('translations.empty.heading'))
            ->emptyStateDescription(__('translations.empty.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('translations.empty.action')),
            ])
            ->defaultSort('group', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTranslations::route('/'),
            'create' => Pages\CreateTranslation::route('/create'),
            'edit' => Pages\EditTranslation::route('/{record}/edit'),
        ];
    }
}
