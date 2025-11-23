<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('FAQ Entry')
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->label('Question')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->label('Category')
                            ->maxLength(120)
                            ->placeholder('Billing, Access, Meters...'),
                        Forms\Components\RichEditor::make('answer')
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
                            ->helperText('Use concise, complete answers. This content is shown publicly on the landing page.'),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('display_order')
                                ->numeric()
                                ->default(0)
                                ->label('Display order')
                                ->helperText('Lower numbers appear first.'),
                            Forms\Components\Toggle::make('is_published')
                                ->label('Published')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_published')
                    ->label('Published')
                    ->options([
                        1 => 'Published',
                        0 => 'Draft',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order', 'asc');
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
