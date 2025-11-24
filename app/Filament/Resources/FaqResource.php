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

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?int $navigationSort = 10;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-question-mark-circle';
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'System';
    }

    /**
     * Only superadmins can access FAQ management.
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
                Section::make('FAQ Entry')
                    ->description('Create or edit FAQ entries displayed on the public landing page')
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->placeholder('What is the billing cycle?')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('category')
                            ->label('Category')
                            ->maxLength(120)
                            ->placeholder('Billing, Access, Meters...')
                            ->helperText('Optional category for grouping related questions'),

                        RichEditor::make('answer')
                            ->label('Answer')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ])
                            ->helperText('Use concise, complete answers. This content is shown publicly on the landing page.')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('display_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->label('Display order')
                                    ->helperText('Lower numbers appear first.'),

                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Only published FAQs appear on the landing page'),
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
                    ->label('Question')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable()
                    ->tooltip(fn (bool $state): string => $state ? 'Visible on landing page' : 'Hidden from public'),

                TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_published')
                    ->label('Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Draft',
                    ])
                    ->native(false),

                SelectFilter::make('category')
                    ->label('Category')
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
                        ->modalHeading('Delete FAQ Entries')
                        ->modalDescription('Are you sure you want to delete these FAQ entries? This action cannot be undone.'),
                ]),
            ])
            ->emptyStateHeading('No FAQ entries yet')
            ->emptyStateDescription('Create your first FAQ entry to help users understand the platform.')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Add First FAQ'),
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
