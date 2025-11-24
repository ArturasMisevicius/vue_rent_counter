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
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
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

    protected static ?string $navigationLabel = 'Languages';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-language';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Localization';
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
                Section::make('Language Details')
                    ->description('Configure language settings for the application')
                    ->schema([
                        TextInput::make('code')
                            ->label('Locale Code')
                            ->maxLength(5)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('en')
                            ->helperText('ISO 639-1 language code (e.g., en, lt, ru)')
                            ->alphaDash()
                            ->lowercase(),

                        TextInput::make('name')
                            ->label('Language Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('English')
                            ->helperText('Display name in English'),

                        TextInput::make('native_name')
                            ->label('Native Name')
                            ->maxLength(255)
                            ->placeholder('English')
                            ->helperText('Display name in the native language'),
                    ])
                    ->columns(3),

                Section::make('Settings')
                    ->description('Control language availability and display')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Only active languages are available for selection'),

                        Toggle::make('is_default')
                            ->label('Default Language')
                            ->inline(false)
                            ->helperText('Only one language should be set as default'),

                        TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Display Order')
                            ->helperText('Lower numbers appear first in language selectors'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Locale')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Language')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('native_name')
                    ->label('Native Name')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? 'Default language' : 'Not default'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? 'Available for selection' : 'Disabled'),

                Tables\Columns\TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All languages')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default')
                    ->placeholder('All languages')
                    ->trueLabel('Default only')
                    ->falseLabel('Non-default only')
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
                        ->modalHeading('Delete Languages')
                        ->modalDescription('Are you sure you want to delete these languages? This may affect translations.'),
                ]),
            ])
            ->emptyStateHeading('No languages configured')
            ->emptyStateDescription('Add languages to enable multi-language support.')
            ->emptyStateActions([
                Actions\CreateAction::make()
                    ->label('Add First Language'),
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
