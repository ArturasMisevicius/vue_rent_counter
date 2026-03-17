<?php

namespace App\Filament\Resources\UtilityServices\Schemas;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UtilityServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.utility_services.sections.details'))
                    ->schema([
                        Hidden::make('organization_id')
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId()),
                        TextInput::make('name')
                            ->label(__('admin.utility_services.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_of_measurement')
                            ->label(__('admin.utility_services.fields.unit_of_measurement'))
                            ->required()
                            ->maxLength(50),
                        Select::make('default_pricing_model')
                            ->label(__('admin.utility_services.fields.default_pricing_model'))
                            ->options(PricingModel::options())
                            ->required(),
                        Select::make('service_type_bridge')
                            ->label(__('admin.utility_services.fields.service_type_bridge'))
                            ->options(ServiceType::options())
                            ->required(),
                        Textarea::make('description')
                            ->label(__('admin.utility_services.fields.description'))
                            ->rows(4),
                        Toggle::make('is_active')
                            ->label(__('admin.utility_services.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
