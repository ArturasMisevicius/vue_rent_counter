<?php

namespace App\Filament\Resources\ServiceConfigurations\Schemas;

use App\Enums\AssignmentScope;
use App\Enums\BillingFrequency;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\ServiceConfigurationStatus;
use App\Enums\ServiceType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Admin\ServiceConfigurations\ValidateServiceConfiguration;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ServiceConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('admin.service_configurations.wizard.steps.service_type'))
                        ->schema([
                            self::helpPanel('service_type'),
                            self::organizationSelect(),
                            TextInput::make('service_name')
                                ->label(__('admin.service_configurations.fields.service_name'))
                                ->helperText(__('admin.service_configurations.helpers.service_name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('service_type')
                                ->label(__('admin.service_configurations.fields.service_type'))
                                ->helperText(__('admin.service_configurations.helpers.service_type'))
                                ->options(ServiceType::options())
                                ->required()
                                ->live(),
                            Select::make('utility_service_id')
                                ->label(__('admin.service_configurations.fields.utility_service'))
                                ->helperText(__('admin.service_configurations.helpers.utility_service'))
                                ->relationship(
                                    name: 'utilityService',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => self::organizationScopedUtilityServices($query),
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                            Textarea::make('invoice_description')
                                ->label(__('admin.service_configurations.fields.invoice_description'))
                                ->helperText(__('admin.service_configurations.helpers.invoice_description'))
                                ->rows(4)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.billing_method'))
                        ->schema([
                            self::helpPanel('billing_method'),
                            Select::make('billing_method')
                                ->label(__('admin.service_configurations.fields.billing_method'))
                                ->helperText(__('admin.service_configurations.helpers.billing_method'))
                                ->options(BillingMethod::options())
                                ->default(BillingMethod::METER_BASED->value)
                                ->required()
                                ->live(),
                            TextInput::make('fixed_amount')
                                ->label(__('admin.service_configurations.fields.fixed_amount'))
                                ->helperText(__('admin.service_configurations.helpers.fixed_amount'))
                                ->numeric()
                                ->minValue(0)
                                ->required(fn (Get $get): bool => $get('billing_method') === BillingMethod::FIXED_MONTHLY->value)
                                ->visible(fn (Get $get): bool => $get('billing_method') === BillingMethod::FIXED_MONTHLY->value),
                            TextInput::make('currency')
                                ->label(__('admin.service_configurations.fields.currency'))
                                ->helperText(__('admin.service_configurations.helpers.currency'))
                                ->default('EUR')
                                ->required()
                                ->maxLength(3),
                            Select::make('billing_frequency')
                                ->label(__('admin.service_configurations.fields.billing_frequency'))
                                ->helperText(__('admin.service_configurations.helpers.billing_frequency'))
                                ->options(BillingFrequency::options())
                                ->default(BillingFrequency::MONTHLY->value)
                                ->required(fn (Get $get): bool => $get('billing_method') === BillingMethod::FIXED_MONTHLY->value),
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
                            TextInput::make('custom_formula')
                                ->label(__('admin.service_configurations.fields.custom_formula'))
                                ->helperText(__('admin.service_configurations.helpers.custom_formula'))
                                ->visible(fn (Get $get): bool => $get('billing_method') === BillingMethod::FORMULA_BASED->value)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.provider_tariff'))
                        ->schema([
                            self::helpPanel('provider_tariff'),
                            Select::make('provider_id')
                                ->label(__('admin.service_configurations.fields.provider'))
                                ->helperText(__('admin.service_configurations.helpers.provider'))
                                ->relationship(
                                    name: 'provider',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => self::organizationScopedProviders($query),
                                )
                                ->searchable()
                                ->preload(),
                            Select::make('tariff_id')
                                ->label(__('admin.service_configurations.fields.tariff'))
                                ->helperText(__('admin.service_configurations.helpers.tariff'))
                                ->relationship(
                                    name: 'tariff',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => self::organizationScopedTariffs($query),
                                )
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('billing_method') === BillingMethod::METER_BASED->value),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.meter_rules'))
                        ->schema([
                            self::helpPanel('meter_rules'),
                            TextInput::make('unit')
                                ->label(__('admin.service_configurations.fields.unit'))
                                ->helperText(__('admin.service_configurations.helpers.unit'))
                                ->maxLength(32)
                                ->required(fn (Get $get): bool => $get('billing_method') === BillingMethod::METER_BASED->value),
                            Toggle::make('meter_rules.require_readings')
                                ->label(__('admin.service_configurations.fields.meter_rules_required'))
                                ->helperText(__('admin.service_configurations.helpers.meter_rules_required'))
                                ->default(true),
                            Toggle::make('meter_rules.allow_estimates')
                                ->label(__('admin.service_configurations.fields.meter_rules_allow_estimates'))
                                ->helperText(__('admin.service_configurations.helpers.meter_rules_allow_estimates'))
                                ->default(false),
                            TextInput::make('meter_rules.minimum_readings')
                                ->label(__('admin.service_configurations.fields.meter_rules_minimum_readings'))
                                ->helperText(__('admin.service_configurations.helpers.meter_rules_minimum_readings'))
                                ->numeric()
                                ->minValue(1)
                                ->default(2),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.assignment_rules'))
                        ->schema([
                            self::helpPanel('assignment_rules'),
                            Select::make('property_id')
                                ->label(__('admin.service_configurations.fields.property'))
                                ->helperText(__('admin.service_configurations.helpers.property'))
                                ->relationship(
                                    name: 'property',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder => self::organizationScopedProperties($query),
                                )
                                ->getOptionLabelFromRecordUsing(fn (Property $record): string => $record->displayName())
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('assignment_scope')
                                ->label(__('admin.service_configurations.fields.assignment_scope'))
                                ->helperText(__('admin.service_configurations.helpers.assignment_scope'))
                                ->options(AssignmentScope::options())
                                ->default(AssignmentScope::PROPERTY->value)
                                ->required(),
                            DatePicker::make('starts_at')
                                ->label(__('admin.service_configurations.fields.starts_at'))
                                ->helperText(__('admin.service_configurations.helpers.starts_at'))
                                ->default(now()->toDateString())
                                ->required(),
                            DatePicker::make('ends_at')
                                ->label(__('admin.service_configurations.fields.ends_at'))
                                ->helperText(__('admin.service_configurations.helpers.ends_at')),
                            Select::make('status')
                                ->label(__('admin.service_configurations.fields.status'))
                                ->helperText(__('admin.service_configurations.helpers.status'))
                                ->options(ServiceConfigurationStatus::options())
                                ->default(ServiceConfigurationStatus::DRAFT->value)
                                ->required(),
                            Toggle::make('assignment_rules.prevent_duplicate_invoice_items')
                                ->label(__('admin.service_configurations.fields.assignment_rules_prevent_duplicates'))
                                ->helperText(__('admin.service_configurations.helpers.assignment_rules_prevent_duplicates'))
                                ->default(true),
                            Toggle::make('is_shared_service')
                                ->label(__('admin.service_configurations.fields.is_shared_service'))
                                ->helperText(__('admin.service_configurations.helpers.is_shared_service')),
                            Select::make('distribution_method')
                                ->label(__('admin.service_configurations.fields.distribution_method'))
                                ->helperText(__('admin.service_configurations.helpers.distribution_method'))
                                ->options(DistributionMethod::options())
                                ->default(DistributionMethod::EQUAL->value)
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.tenant_visibility'))
                        ->schema([
                            self::helpPanel('tenant_visibility'),
                            Toggle::make('tenant_visible')
                                ->label(__('admin.service_configurations.fields.tenant_visible'))
                                ->helperText(__('admin.service_configurations.helpers.tenant_visible'))
                                ->live(),
                            TextInput::make('tenant_visible_name')
                                ->label(__('admin.service_configurations.fields.tenant_visible_name'))
                                ->helperText(__('admin.service_configurations.helpers.tenant_visible_name'))
                                ->required(fn (Get $get): bool => (bool) $get('tenant_visible'))
                                ->maxLength(255),
                            Textarea::make('tenant_visible_description')
                                ->label(__('admin.service_configurations.fields.tenant_visible_description'))
                                ->helperText(__('admin.service_configurations.helpers.tenant_visible_description'))
                                ->required(fn (Get $get): bool => (bool) $get('tenant_visible'))
                                ->rows(4)
                                ->columnSpanFull(),
                            Toggle::make('show_formula_to_tenant')
                                ->label(__('admin.service_configurations.fields.show_formula_to_tenant'))
                                ->helperText(__('admin.service_configurations.helpers.show_formula_to_tenant')),
                            Toggle::make('show_provider_to_tenant')
                                ->label(__('admin.service_configurations.fields.show_provider_to_tenant'))
                                ->helperText(__('admin.service_configurations.helpers.show_provider_to_tenant')),
                            Toggle::make('show_readings_to_tenant')
                                ->label(__('admin.service_configurations.fields.show_readings_to_tenant'))
                                ->helperText(__('admin.service_configurations.helpers.show_readings_to_tenant')),
                            Textarea::make('internal_note')
                                ->label(__('admin.service_configurations.fields.internal_note'))
                                ->helperText(__('admin.service_configurations.helpers.internal_note'))
                                ->rows(4)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make(__('admin.service_configurations.wizard.steps.preview_validation'))
                        ->schema([
                            self::helpPanel('preview_validation'),
                            Section::make(__('admin.service_configurations.sections.validation'))
                                ->schema([
                                    Placeholder::make('validation_preview')
                                        ->label(__('admin.service_configurations.preview.title'))
                                        ->content(fn (Get $get): HtmlString => self::validationContent($get))
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull(),
            ]);
    }

    private static function helpPanel(string $step): Placeholder
    {
        return Placeholder::make("help_{$step}")
            ->label(__('admin.service_configurations.guidance.title'))
            ->content(new HtmlString('<p class="text-sm text-gray-600">'.e(__('admin.service_configurations.wizard.help.'.$step)).'</p>'))
            ->columnSpanFull();
    }

    private static function organizationSelect(): Select
    {
        return Select::make('organization_id')
            ->label(__('superadmin.organizations.singular'))
            ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
            ->options(fn (): array => Organization::query()
                ->forSuperadminControlPlane()
                ->pluck('name', 'id')
                ->all())
            ->searchable()
            ->preload()
            ->dehydratedWhenHidden()
            ->required(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
            ->visible(fn (): bool => (self::currentUser()?->isSuperadmin() ?? false)
                && app(OrganizationContext::class)->currentOrganizationId() === null);
    }

    private static function organizationScopedProperties(Builder $query): Builder
    {
        $query->select(['id', 'organization_id', 'building_id', 'name', 'unit_number']);
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null && (self::currentUser()?->isSuperadmin() ?? false)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    private static function organizationScopedUtilityServices(Builder $query): Builder
    {
        $query->select(['id', 'organization_id', 'name', 'unit_of_measurement', 'is_global_template']);
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null && (self::currentUser()?->isSuperadmin() ?? false)) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($organizationId): void {
            $builder
                ->where('organization_id', $organizationId)
                ->orWhere('is_global_template', true);
        });
    }

    private static function organizationScopedProviders(Builder $query): Builder
    {
        $query->select(['id', 'organization_id', 'name']);
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null && (self::currentUser()?->isSuperadmin() ?? false)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    private static function organizationScopedTariffs(Builder $query): Builder
    {
        $query->select(['id', 'provider_id', 'name']);
        $organizationId = app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null && (self::currentUser()?->isSuperadmin() ?? false)) {
            return $query;
        }

        return $query->whereHas(
            'provider',
            fn (Builder $providerQuery): Builder => $providerQuery->where('organization_id', $organizationId),
        );
    }

    private static function validationContent(Get $get): HtmlString
    {
        $state = self::stateFromGet($get);
        $result = app(ValidateServiceConfiguration::class)->handle($state);
        $message = $result['blocking_errors'] === []
            ? __('admin.service_configurations.preview.valid')
            : __('admin.service_configurations.preview.blocked');

        return new HtmlString(
            '<div class="space-y-3">'
            .'<p class="text-sm font-medium">'.e($message).'</p>'
            .self::validationList(__('admin.service_configurations.validation.headings.blocking_errors'), $result['blocking_errors'])
            .self::validationList(__('admin.service_configurations.validation.headings.warnings'), $result['warnings'])
            .self::validationList(__('admin.service_configurations.validation.headings.recommendations'), $result['recommendations'])
            .'<p class="text-sm text-gray-600">'.e(self::calculationPreview($state)).'</p>'
            .'</div>',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function stateFromGet(Get $get): array
    {
        return [
            'service_name' => $get('service_name'),
            'service_type' => $get('service_type'),
            'billing_method' => $get('billing_method'),
            'provider_id' => $get('provider_id'),
            'tariff_id' => $get('tariff_id'),
            'unit' => $get('unit'),
            'currency' => $get('currency'),
            'fixed_amount' => $get('fixed_amount'),
            'billing_frequency' => $get('billing_frequency'),
            'tenant_visible' => $get('tenant_visible'),
            'tenant_visible_name' => $get('tenant_visible_name'),
            'tenant_visible_description' => $get('tenant_visible_description'),
            'custom_formula' => $get('custom_formula'),
            'invoice_description' => $get('invoice_description'),
            'meter_rules' => [
                'require_readings' => $get('meter_rules.require_readings'),
                'allow_estimates' => $get('meter_rules.allow_estimates'),
                'minimum_readings' => $get('meter_rules.minimum_readings'),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $items
     */
    private static function validationList(string $title, array $items): string
    {
        if ($items === []) {
            return '<div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">'.e($title).'</p><p class="text-sm text-gray-600">'.e(__('admin.service_configurations.validation.none')).'</p></div>';
        }

        $list = collect($items)
            ->map(fn (string $item): string => '<li>'.e($item).'</li>')
            ->implode('');

        return '<div><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">'.e($title).'</p><ul class="list-disc space-y-1 ps-5 text-sm text-gray-700">'.$list.'</ul></div>';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private static function calculationPreview(array $state): string
    {
        $billingMethod = BillingMethod::tryFrom((string) ($state['billing_method'] ?? ''));

        return match ($billingMethod) {
            BillingMethod::FIXED_MONTHLY => __('admin.service_configurations.preview.estimated_fixed', [
                'currency' => $state['currency'] ?: 'EUR',
                'amount' => $state['fixed_amount'] ?: '0.00',
            ]),
            BillingMethod::METER_BASED => __('admin.service_configurations.preview.estimated_meter'),
            BillingMethod::MANUAL,
            BillingMethod::ONE_TIME => __('admin.service_configurations.preview.manual_waits'),
            BillingMethod::INCLUDED_FREE => __('admin.service_configurations.preview.included_free'),
            default => __('admin.service_configurations.preview.valid'),
        };
    }

    private static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
