<?php

namespace App\Filament\Resources\ServiceConfigurations\Schemas;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ServiceConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.service_configurations.sections.details'))
                    ->description(__('admin.service_configurations.guidance.description'))
                    ->schema([
                        Placeholder::make('configuration_guide')
                            ->label(__('admin.service_configurations.guidance.title'))
                            ->content(self::guidanceContent())
                            ->columnSpanFull(),
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
                        Select::make('property_id')
                            ->label(__('admin.service_configurations.fields.property'))
                            ->helperText(__('admin.service_configurations.helpers.property'))
                            ->relationship(
                                name: 'property',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'organization_id', 'building_id', 'name', 'unit_number']);

                                    $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                                    $user = Auth::user();

                                    if ($organizationId === null && $user instanceof User && $user->isSuperadmin()) {
                                        return $query;
                                    }

                                    return $query->where('organization_id', $organizationId);
                                },
                            )
                            ->getOptionLabelFromRecordUsing(fn (Property $record): string => $record->displayName())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('utility_service_id')
                            ->label(__('admin.service_configurations.fields.utility_service'))
                            ->helperText(__('admin.service_configurations.helpers.utility_service'))
                            ->relationship(
                                name: 'utilityService',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'organization_id', 'name', 'unit_of_measurement', 'is_global_template']);

                                    $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                                    $user = Auth::user();

                                    if ($organizationId === null && $user instanceof User && $user->isSuperadmin()) {
                                        return $query;
                                    }

                                    return $query->where(function (Builder $builder) use ($organizationId): void {
                                        $builder
                                            ->where('organization_id', $organizationId)
                                            ->orWhere('is_global_template', true);
                                    });
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('invoice_description')
                            ->label(__('admin.service_configurations.fields.invoice_description'))
                            ->helperText(__('admin.service_configurations.helpers.invoice_description'))
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Select::make('provider_id')
                            ->label(__('admin.service_configurations.fields.provider'))
                            ->helperText(__('admin.service_configurations.helpers.provider'))
                            ->relationship(
                                name: 'provider',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'organization_id', 'name']);

                                    $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                                    $user = Auth::user();

                                    if ($organizationId === null && $user instanceof User && $user->isSuperadmin()) {
                                        return $query;
                                    }

                                    return $query->where('organization_id', $organizationId);
                                },
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('tariff_id')
                            ->label(__('admin.service_configurations.fields.tariff'))
                            ->helperText(__('admin.service_configurations.helpers.tariff'))
                            ->relationship(
                                name: 'tariff',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'provider_id', 'name']);

                                    $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                                    $user = Auth::user();

                                    if ($organizationId === null && $user instanceof User && $user->isSuperadmin()) {
                                        return $query;
                                    }

                                    return $query->whereHas(
                                        'provider',
                                        fn (Builder $providerQuery): Builder => $providerQuery->where('organization_id', $organizationId),
                                    );
                                },
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('pricing_model')
                            ->label(__('admin.service_configurations.fields.pricing_model'))
                            ->helperText(__('admin.service_configurations.helpers.pricing_model'))
                            ->options(PricingModel::options())
                            ->required(),
                        TextInput::make('rate_schedule.unit_rate')
                            ->label(__('admin.service_configurations.fields.unit_rate'))
                            ->helperText(__('admin.service_configurations.helpers.unit_rate'))
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('rate_schedule.base_fee')
                            ->label(__('admin.service_configurations.fields.base_fee'))
                            ->helperText(__('admin.service_configurations.helpers.base_fee'))
                            ->numeric()
                            ->minValue(0),
                        Select::make('distribution_method')
                            ->label(__('admin.service_configurations.fields.distribution_method'))
                            ->helperText(__('admin.service_configurations.helpers.distribution_method'))
                            ->options(DistributionMethod::options())
                            ->required(),
                        Toggle::make('is_shared_service')
                            ->label(__('admin.service_configurations.fields.is_shared_service'))
                            ->helperText(__('admin.service_configurations.helpers.is_shared_service')),
                        DatePicker::make('effective_from')
                            ->label(__('admin.service_configurations.fields.effective_from'))
                            ->helperText(__('admin.service_configurations.helpers.effective_from'))
                            ->required(),
                        DatePicker::make('effective_until')
                            ->label(__('admin.service_configurations.fields.effective_until'))
                            ->helperText(__('admin.service_configurations.helpers.effective_until')),
                        TextInput::make('area_type')
                            ->label(__('admin.service_configurations.fields.area_type'))
                            ->helperText(__('admin.service_configurations.helpers.area_type')),
                        TextInput::make('custom_formula')
                            ->label(__('admin.service_configurations.fields.custom_formula'))
                            ->helperText(__('admin.service_configurations.helpers.custom_formula')),
                        Toggle::make('is_active')
                            ->label(__('admin.service_configurations.fields.is_active'))
                            ->helperText(__('admin.service_configurations.helpers.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    private static function guidanceContent(): HtmlString
    {
        $items = __('admin.service_configurations.guidance.items');

        if (! is_array($items)) {
            return new HtmlString('');
        }

        $content = collect($items)
            ->map(fn (string $item): string => '<li>'.e($item).'</li>')
            ->implode('');

        return new HtmlString('<ul class="list-disc space-y-1 ps-5">'.$content.'</ul>');
    }
}
