<?php

namespace App\Filament\Resources\Tariffs\Schemas;

use App\Enums\TariffType;
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
                        TextInput::make('remote_id')
                            ->label(__('admin.tariffs.fields.remote_id'))
                            ->maxLength(255),
                        Select::make('configuration.type')
                            ->label(__('admin.tariffs.fields.type'))
                            ->options(
                                collect(TariffType::cases())
                                    ->mapWithKeys(fn (TariffType $type): array => [
                                        $type->value => __('admin.tariffs.types.'.$type->value),
                                    ])
                                    ->all(),
                            )
                            ->required(),
                        TextInput::make('configuration.currency')
                            ->label(__('admin.tariffs.fields.currency'))
                            ->required()
                            ->maxLength(10),
                        TextInput::make('configuration.rate')
                            ->label(__('admin.tariffs.fields.rate'))
                            ->numeric(),
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
