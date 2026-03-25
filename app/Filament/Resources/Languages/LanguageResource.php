<?php

namespace App\Filament\Resources\Languages;

use App\Enums\LanguageStatus;
use App\Filament\Concerns\AuthorizesSuperadminAccess;
use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LanguageResource extends Resource
{
    use AuthorizesSuperadminAccess;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('superadmin.languages_resource.sections.details'))
                ->schema([
                    TextInput::make('code')
                        ->label(__('superadmin.languages_resource.fields.code'))
                        ->required()
                        ->maxLength(10),
                    TextInput::make('name')
                        ->label(__('superadmin.languages_resource.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('native_name')
                        ->label(__('superadmin.languages_resource.fields.native_name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('status')
                        ->label(__('superadmin.languages_resource.fields.status'))
                        ->options(LanguageStatus::options())
                        ->default(LanguageStatus::ACTIVE->value)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('superadmin.languages_resource.columns.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('superadmin.languages_resource.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('native_name')
                    ->label(__('superadmin.languages_resource.columns.native_name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('superadmin.languages_resource.columns.status'))
                    ->badge(),
                IconColumn::make('is_default')
                    ->label(__('superadmin.languages_resource.columns.default'))
                    ->boolean(),
            ])
            ->defaultSort('name');
    }

    public static function getModelLabel(): string
    {
        return __('superadmin.languages_resource.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('superadmin.languages_resource.plural');
    }

    /**
     * @return Builder<Language>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id',
                'code',
                'name',
                'native_name',
                'status',
                'is_default',
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
