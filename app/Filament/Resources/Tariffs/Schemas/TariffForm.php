<?php

namespace App\Filament\Resources\Tariffs\Schemas;

use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TariffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tariffs.sections.details'))
                    ->schema([
                        Select::make('provider_id')
                            ->label(__('admin.tariffs.fields.provider'))
                            ->relationship(
                                name: 'provider',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'name', 'service_type'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.tariffs.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('configuration.type')
                            ->label(__('admin.tariffs.fields.pricing_type'))
                            ->options([
                                'flat' => __('admin.tariffs.types.flat'),
                                'time_of_use' => __('admin.tariffs.types.time_of_use'),
                            ])
                            ->default('flat')
                            ->required(),
                        TextInput::make('configuration.rate')
                            ->label(__('admin.tariffs.fields.rate'))
                            ->numeric()
                            ->required(),
                        TextInput::make('configuration.currency')
                            ->label(__('admin.tariffs.fields.currency'))
                            ->default('EUR')
                            ->required(),
                        DateTimePicker::make('active_from')
                            ->label(__('admin.tariffs.fields.active_from'))
                            ->required(),
                        DateTimePicker::make('active_until')
                            ->label(__('admin.tariffs.fields.active_until')),
                    ])
                    ->columns(2),
            ]);
    }
}
