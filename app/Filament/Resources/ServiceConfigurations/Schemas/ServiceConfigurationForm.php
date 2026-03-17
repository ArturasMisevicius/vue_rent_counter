<?php

namespace App\Filament\Resources\ServiceConfigurations\Schemas;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ServiceConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.service_configurations.sections.details'))
                    ->schema([
                        Hidden::make('organization_id')
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId()),
                        Select::make('property_id')
                            ->label(__('admin.service_configurations.fields.property'))
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
                        Select::make('utility_service_id')
                            ->label(__('admin.service_configurations.fields.utility_service'))
                            ->relationship(
                                name: 'utilityService',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'name', 'unit_of_measurement', 'is_global_template'])
                                    ->where(function (Builder $builder): void {
                                        $builder
                                            ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                                            ->orWhere('is_global_template', true);
                                    }),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('provider_id')
                            ->label(__('admin.service_configurations.fields.provider'))
                            ->relationship(
                                name: 'provider',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'name'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('tariff_id')
                            ->label(__('admin.service_configurations.fields.tariff'))
                            ->relationship(
                                name: 'tariff',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'provider_id', 'name'])
                                    ->whereHas('provider', fn (Builder $providerQuery): Builder => $providerQuery->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('pricing_model')
                            ->label(__('admin.service_configurations.fields.pricing_model'))
                            ->options(PricingModel::options())
                            ->required(),
                        TextInput::make('rate_schedule.unit_rate')
                            ->label(__('admin.service_configurations.fields.unit_rate'))
                            ->numeric(),
                        TextInput::make('rate_schedule.base_fee')
                            ->label(__('admin.service_configurations.fields.base_fee'))
                            ->numeric(),
                        Select::make('distribution_method')
                            ->label(__('admin.service_configurations.fields.distribution_method'))
                            ->options(DistributionMethod::options())
                            ->required(),
                        Toggle::make('is_shared_service')
                            ->label(__('admin.service_configurations.fields.is_shared_service')),
                        TextInput::make('effective_from')
                            ->label(__('admin.service_configurations.fields.effective_from'))
                            ->required(),
                        TextInput::make('effective_until')
                            ->label(__('admin.service_configurations.fields.effective_until')),
                        TextInput::make('area_type')
                            ->label(__('admin.service_configurations.fields.area_type')),
                        TextInput::make('custom_formula')
                            ->label(__('admin.service_configurations.fields.custom_formula')),
                        Toggle::make('is_active')
                            ->label(__('admin.service_configurations.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
