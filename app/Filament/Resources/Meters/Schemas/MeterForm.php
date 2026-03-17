<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class MeterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meters.sections.details'))
                    ->schema([
                        Select::make('property_id')
                            ->label(__('admin.meters.fields.property'))
                            ->relationship(
                                name: 'property',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.meters.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('identifier')
                            ->label(__('admin.meters.fields.identifier'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('admin.meters.fields.type'))
                            ->options(
                                collect(MeterType::cases())
                                    ->mapWithKeys(fn (MeterType $type): array => [
                                        $type->value => __('admin.meters.types.'.$type->value),
                                    ])
                                    ->all(),
                            )
                            ->required(),
                        TextInput::make('unit')
                            ->label(__('admin.meters.fields.unit'))
                            ->placeholder(__('admin.meters.fields.unit_placeholder')),
                        Select::make('status')
                            ->label(__('admin.meters.fields.status'))
                            ->options([
                                MeterStatus::ACTIVE->value => __('admin.meters.statuses.active'),
                                MeterStatus::INACTIVE->value => __('admin.meters.statuses.inactive'),
                            ])
                            ->default(MeterStatus::ACTIVE->value)
                            ->required(),
                        DatePicker::make('installed_at')
                            ->label(__('admin.meters.fields.installed_at')),
                    ])
                    ->columns(2),
            ]);
    }
}
