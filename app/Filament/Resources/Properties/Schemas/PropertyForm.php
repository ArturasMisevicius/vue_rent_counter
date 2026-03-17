<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\PropertyType;
use App\Models\Building;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.details'))
                    ->schema([
                        Select::make('building_id')
                            ->label(__('admin.properties.columns.building'))
                            ->options(fn (): array => Building::query()
                                ->select(['id', 'name', 'organization_id'])
                                ->where('organization_id', auth()->user()?->organization_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.properties.columns.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_number')
                            ->label(__('admin.properties.columns.unit_number'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('admin.properties.columns.type'))
                            ->options([
                                PropertyType::APARTMENT->value => __('admin.properties.types.apartment'),
                                PropertyType::HOUSE->value => __('admin.properties.types.house'),
                                PropertyType::OFFICE->value => __('admin.properties.types.office'),
                                PropertyType::STORAGE->value => __('admin.properties.types.storage'),
                            ])
                            ->required(),
                        TextInput::make('floor_area_sqm')
                            ->label(__('admin.properties.columns.floor_area_sqm'))
                            ->numeric()
                            ->suffix('sqm'),
                    ])
                    ->columns(2),
            ]);
    }
}
