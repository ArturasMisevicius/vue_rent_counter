<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\PropertyType;
use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.details'))
                    ->schema([
                        Select::make('building_id')
                            ->label(__('admin.properties.fields.building'))
                            ->relationship(
                                name: 'building',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'name'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.properties.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_number')
                            ->label(__('admin.properties.fields.unit_number'))
                            ->required()
                            ->maxLength(50),
                        Select::make('type')
                            ->label(__('admin.properties.fields.type'))
                            ->options(
                                collect(PropertyType::cases())
                                    ->mapWithKeys(fn (PropertyType $type): array => [
                                        $type->value => __('admin.properties.types.'.$type->value),
                                    ])
                                    ->all(),
                            )
                            ->required(),
                        TextInput::make('floor_area_sqm')
                            ->label(__('admin.properties.fields.floor_area_sqm'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(2),
            ]);
    }
}
