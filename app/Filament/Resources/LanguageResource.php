<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationLabel = 'Languages';

    protected static ?string $navigationGroup = 'Localization';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === UserRole::SUPERADMIN;
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role === UserRole::SUPERADMIN;
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->role === UserRole::SUPERADMIN;
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->role === UserRole::SUPERADMIN;
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->role === UserRole::SUPERADMIN;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Locale code')
                    ->maxLength(5)
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('en'),
                Forms\Components\TextInput::make('name')
                    ->label('Language name')
                    ->required(),
                Forms\Components\TextInput::make('native_name')
                    ->label('Native name'),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\Toggle::make('is_default')
                        ->label('Default')
                        ->helperText('Only one language should be default.'),
                    Forms\Components\TextInput::make('display_order')
                        ->numeric()
                        ->default(0)
                        ->label('Order'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Locale')->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('native_name')->label('Native')->sortable(),
                Tables\Columns\IconColumn::make('is_default')->label('Default')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('display_order')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
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
