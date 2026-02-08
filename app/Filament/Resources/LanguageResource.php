<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-language';
    }

    public static function getNavigationGroup(): string|null
    {
        return __('app.nav_groups.localization');
    }

    public static function getNavigationLabel(): string
    {
        return __('locales.navigation');
    }

    /**
     * Only superadmins can access language management.
     *
     * Note: Authorization for CRUD operations is handled by LanguagePolicy.
     * This method only controls navigation visibility.
     *
     * @see \App\Policies\LanguagePolicy
     */
    public static function shouldRegisterNavigation(): bool
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
                            ->maxLength(5)           // SECURITY: Prevents buffer overflow attacks
                            ->minLength(2)           // SECURITY: Ensures valid ISO 639-1 codes
                            ->required()             // SECURITY: Prevents null injection
                            ->unique(ignoreRecord: true)  // SECURITY: Prevents duplicate codes
                            ->placeholder(__('locales.placeholders.code'))
                            ->helperText(__('locales.helper_text.code'))
                            ->alphaDash()            // SECURITY: Restricts to alphanumeric and dash
                            ->regex('/^[a-z]{2}(-[A-Z]{2})?$/')  // SECURITY: ISO 639-1 format only
                            ->validationMessages([
                                'regex' => __('locales.validation.code_format'),
                            ]),
                        // PERFORMANCE: Lowercase conversion handled by Language model mutator
                        // (see Language::code() attribute). Removed redundant form transformations
                        // to eliminate duplicate string operations on every render/save.
                        // SECURITY: Model mutator provides defense-in-depth normalization

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
                            ->helperText(__('locales.helper_text.default'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, ?Model $record) {
                                if ($state) {
                                    // When setting as default, unset other defaults
                                    Language::where('is_default', true)
                                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                        ->update(['is_default' => false]);
                                }
                            })
                            ->disabled(fn (?Model $record): bool => $record?->is_default && Language::where('is_default', true)->count() === 1
                            )
                            ->dehydrated(fn ($state) => $state === true),

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
                    ->color(fn (Language $record): string => match (true) {
                        $record->is_default => 'success',
                        $record->is_active => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn (Language $record): ?string => $record->is_default ? 'heroicon-m-star' : null
                    )
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->copyable()
                    ->copyMessage(__('locales.messages.code_copied'))
                    ->copyMessageDuration(1500),

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
                Actions\Action::make('set_default')
                    ->label(__('locales.actions.set_default'))
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('locales.modals.set_default.heading'))
                    ->modalDescription(__('locales.modals.set_default.description'))
                    ->action(function (Language $record) {
                        // Unset all other defaults
                        Language::where('is_default', true)
                            ->where('id', '!=', $record->id)
                            ->update(['is_default' => false]);

                        // Set this language as default and ensure it's active
                        $record->update([
                            'is_default' => true,
                            'is_active' => true,
                        ]);
                    })
                    ->visible(fn (?Language $record): bool =>
                        // Only show for non-default languages
                        $record && ! $record->is_default
                    )
                    ->successNotificationTitle(__('locales.notifications.default_set')),

                Actions\Action::make('toggle_active')
                    ->label(fn (Language $record): string => $record->is_active
                            ? __('locales.actions.deactivate')
                            : __('locales.actions.activate')
                    )
                    ->icon(fn (Language $record): string => $record->is_active
                            ? 'heroicon-o-x-circle'
                            : 'heroicon-o-check-circle'
                    )
                    ->color(fn (Language $record): string => $record->is_active ? 'danger' : 'success'
                    )
                    ->requiresConfirmation()
                    ->action(fn (Language $record) => $record->update(['is_active' => ! $record->is_active])
                    )
                    ->visible(fn (Language $record): bool =>
                        // Don't allow deactivating the default language
                        ! $record->is_default || ! $record->is_active
                    ),

                Tables\Actions\EditAction::make()
                    ->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->before(function (Language $record) {
                        // Prevent deleting default language
                        if ($record->is_default) {
                            throw new \Exception(__('locales.errors.cannot_delete_default'));
                        }
                        // Prevent deleting last active language
                        if ($record->is_active && Language::where('is_active', true)->count() === 1) {
                            throw new \Exception(__('locales.errors.cannot_delete_last_active'));
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('locales.actions.bulk_activate'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])
                        )
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('locales.actions.bulk_deactivate'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            // Prevent deactivating default language
                            $defaultLanguage = $records->firstWhere('is_default', true);
                            if ($defaultLanguage) {
                                throw new \Exception(__('locales.errors.cannot_deactivate_default'));
                            }

                            $records->each->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('locales.modals.delete.heading'))
                        ->modalDescription(__('locales.modals.delete.description'))
                        ->before(function (Collection $records) {
                            // Prevent deleting default language
                            if ($records->contains('is_default', true)) {
                                throw new \Exception(__('locales.errors.cannot_delete_default'));
                            }
                        }),
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
