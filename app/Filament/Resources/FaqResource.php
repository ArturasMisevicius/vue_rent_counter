<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use App\Models\User;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Filament resource for managing FAQ entries.
 *
 * Provides CRUD operations for FAQ entries with:
 * - Superadmin-only access
 * - Rich text editor for answers
 * - Display order management
 * - Publication status control
 *
 * @see \App\Models\Faq
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-question-mark-circle';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.system_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('faq.labels.resource');
    }

    /**
     * Only superadmins can access FAQ management.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('faq.sections.faq_entry'))
                    ->description(__('faq.helper_text.entry'))
                    ->schema([
                        TextInput::make('question')
                            ->label(__('faq.labels.question'))
                            ->placeholder(__('faq.placeholders.question'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('category')
                            ->label(__('faq.labels.category'))
                            ->maxLength(120)
                            ->placeholder(__('faq.placeholders.category'))
                            ->helperText(__('faq.helper_text.category')),

                        RichEditor::make('answer')
                            ->label(__('faq.labels.answer'))
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ])
                            ->helperText(__('faq.helper_text.answer'))
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('display_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label(__('faq.labels.display_order'))
                                    ->helperText(__('faq.helper_text.order')),

                                Toggle::make('is_published')
                                    ->label(__('faq.labels.published'))
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText(__('faq.helper_text.published')),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->label(__('faq.labels.question'))
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('category')
                    ->label(__('faq.labels.category'))
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('app.common.dash')),

                IconColumn::make('is_published')
                    ->label(__('faq.labels.published'))
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? __('faq.helper_text.visible') : __('faq.helper_text.hidden')),

                TextColumn::make('display_order')
                    ->label(__('faq.labels.order'))
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('updated_at')
                    ->label(__('faq.labels.last_updated'))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_published')
                    ->label(__('faq.filters.status'))
                    ->options([
                        1 => __('faq.filters.options.published'),
                        0 => __('faq.filters.options.draft'),
                    ])
                    ->native(false),

                SelectFilter::make('category')
                    ->label(__('faq.filters.category'))
                    ->options(fn (): array => Faq::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->pluck('category', 'category')
                        ->toArray()
                    )
                    ->searchable()
                    ->native(false),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('faq.modals.delete.heading'))
                        ->modalDescription(__('faq.modals.delete.description')),
                ]),
            ])
            ->emptyStateHeading(__('faq.empty.heading'))
            ->emptyStateDescription(__('faq.empty.description'))
            ->emptyStateActions([
                CreateAction::make()
                    ->label(__('faq.actions.add_first')),
            ])
            ->defaultSort('display_order', 'asc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
