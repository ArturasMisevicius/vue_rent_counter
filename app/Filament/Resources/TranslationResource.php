<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\TranslationResource\Pages;
use App\Models\Language;
use App\Models\Translation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Translations';

    protected static ?string $navigationGroup = 'Localization';

    protected static ?int $navigationSort = 2;

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
        $languages = Language::query()->where('is_active', true)->orderBy('display_order')->get();

        return $form
            ->schema([
                Forms\Components\TextInput::make('group')
                    ->required()
                    ->maxLength(120)
                    ->label('Group (php file name)')
                    ->placeholder('app'),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->label('Key (dot notation supported)')
                    ->placeholder('nav.dashboard'),
                Forms\Components\Section::make('Translations')
                    ->description('Values are written to PHP lang files (no JSON).')
                    ->schema(
                        $languages->map(function (Language $language) {
                            return Forms\Components\Textarea::make("values.{$language->code}")
                                ->label("{$language->name} ({$language->code})")
                                ->rows(2)
                                ->columnSpanFull();
                        })->all()
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('key')->sortable()->searchable(),
                Tables\Columns\TextColumn::make("values->{$defaultLocale}")
                    ->label("{$defaultLocale} value")
                    ->limit(50),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group');
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
