<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Filament resource for managing languages.
 *
 * Provides CRUD operations for language configuration with:
 * - Superadmin-only access
 * - Locale code management
 * - Default language selection
 * - Display order control
 *
 * @see \App\Models\Language
 */
class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-language';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.localization');
    }

    public static function getNavigationLabel(): string
    {
        return __('locales.navigation');
    }

    /**
     * Only superadmins can access language management.
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
        return $schema
            ->schema([
                Section::make(__('locales.sections.details'))
                    ->description(__('locales.helper_text.details'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('locales.labels.code'))
                            ->maxLength(5)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder(__('locales.placeholders.code'))
                            ->helperText(__('locales.helper_text.code'))
                            ->alphaDash()
                            ->lowercase(),

                        TextInput::make('name')
                            ->label(__('locales.labels.name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('locales.placeholders.name'))
                            ->helperText(__('locales.helper_text.name')),

                        TextInput::make('native_name')
                            ->label(__('locales.labels.native_name'))
                            ->maxLength(255)
                            ->placeholder(__('locales.placeholders.native_name'))
                            ->helperText(__('locales.helper_text.native_name')),
                    ])
                    ->columns(3),

                Section::make(__('locales.sections.settings'))
                    ->description(__('locales.helper_text.details'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('locales.labels.active'))
                            ->default(true)
                            ->inline(false)
                            ->helperText(__('locales.helper_text.active')),

                        Toggle::make('is_default')
                            ->label(__('locales.labels.default'))
                            ->inline(false)
                            ->helperText(__('locales.helper_text.default')),

                        TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label(__('locales.labels.order'))
                            ->helperText(__('locales.helper_text.order')),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('locales.labels.locale'))
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('locales.labels.name'))
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('native_name')
                    ->label(__('locales.labels.native_name'))
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('app.common.dash')),

                Tables\Columns\IconColumn::make('is_default')
                    ->label(__('locales.labels.default'))
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? __('locales.helper_text.default') : __('locales.helper_text.active')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('locales.labels.active'))
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? __('locales.helper_text.active') : __('locales.helper_text.details')),

                Tables\Columns\TextColumn::make('display_order')
                    ->label(__('locales.labels.order'))
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('locales.labels.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('locales.labels.active'))
                    ->placeholder(__('locales.filters.active_placeholder'))
                    ->trueLabel(__('locales.filters.active_only'))
                    ->falseLabel(__('locales.filters.inactive_only'))
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label(__('locales.labels.default'))
                    ->placeholder(__('locales.filters.default_placeholder'))
                    ->trueLabel(__('locales.filters.default_only'))
                    ->falseLabel(__('locales.filters.non_default_only'))
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
                        ->modalHeading(__('locales.modals.delete.heading'))
                        ->modalDescription(__('locales.modals.delete.description')),
                ]),
            ])
            ->emptyStateHeading(__('locales.empty.heading'))
            ->emptyStateDescription(__('locales.empty.description'))
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('locales.empty.action')),
            ])
            ->defaultSort('display_order', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
