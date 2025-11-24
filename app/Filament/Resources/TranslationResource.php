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

    protected static ?string $navigationLabel = 'Translations';

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Localization';
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
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return $schema
            ->schema([
                Forms\Components\Section::make('Translation Key')
                    ->description('Define the group and key for this translation')
                    ->schema([
                        Forms\Components\TextInput::make('group')
                            ->required()
                            ->maxLength(120)
                            ->label('Group')
                            ->placeholder('app')
                            ->helperText('PHP file name in lang/{locale}/ directory (e.g., "app" for app.php)')
                            ->alphaDash(),

                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->label('Key')
                            ->placeholder('nav.dashboard')
                            ->helperText('Translation key with dot notation support (e.g., "nav.dashboard")')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Translation Values')
                    ->description('Provide translations for each active language. Values are written to PHP lang files.')
                    ->schema(
                        $languages->map(function (Language $language) {
                            return Forms\Components\Textarea::make("values.{$language->code}")
                                ->label("{$language->name} ({$language->code})")
                                ->rows(3)
                                ->placeholder("Enter {$language->name} translation...")
                                ->helperText($language->is_default ? 'Default language' : '')
                                ->columnSpanFull();
                        })->all()
                    )
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $defaultLocale = Language::query()
            ->where('is_default', true)
            ->value('code') ?? 'en';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Translation key copied')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make("values->{$defaultLocale}")
                    ->label(strtoupper($defaultLocale).' Value')
                    ->limit(50)
                    ->wrap()
                    ->placeholder('—')
                    ->tooltip(fn (?string $state): ?string => $state && strlen($state) > 50 ? $state : null),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Group')
                    ->options(fn (): array => Translation::query()
                        ->distinct()
                        ->pluck('group', 'group')
                        ->toArray()
                    )
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Translations')
                        ->modalDescription('Are you sure you want to delete these translations? This will affect the application UI.'),
                ]),
            ])
            ->emptyStateHeading('No translations yet')
            ->emptyStateDescription('Create translation entries to manage multi-language content.')
            ->emptyStateActions([
                Actions\CreateAction::make()
                    ->label('Add First Translation'),
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
