<?php

namespace App\Filament\Resources\UtilityServices\Schemas;

use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\UnitOfMeasurement;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UtilityServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.utility_services.sections.details'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                            ->options(fn (): array => Organization::query()
                                ->forSuperadminControlPlane()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->dehydratedWhenHidden()
                            ->required(function (): bool {
                                $user = Auth::user();

                                if (! $user instanceof User) {
                                    return false;
                                }

                                return $user->isSuperadmin();
                            })
                            ->visible(function (): bool {
                                $user = Auth::user();

                                if (! $user instanceof User) {
                                    return false;
                                }

                                return $user->isSuperadmin()
                                    && app(OrganizationContext::class)->currentOrganizationId() === null;
                            }),
                        TextInput::make('name')
                            ->label(__('admin.utility_services.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('unit_of_measurement')
                            ->label(__('admin.utility_services.fields.unit_of_measurement'))
                            ->options(UnitOfMeasurement::options()),
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
