<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Schemas;

use App\Enums\ExtraChargeStatus;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\BillingPeriod;
use App\Models\ExtraChargeType;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\Billing\UniversalBillingCalculator;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExtraChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /**
     * @return array<int, mixed>
     */
    public static function components(
        ?int $tenantId = null,
        ?int $propertyId = null,
        ?bool $recurringDefault = null,
    ): array {
        return [
            Section::make(__('admin.extra_charges.sections.assignment'))
                ->schema([
                    Select::make('organization_id')
                        ->label(__('superadmin.organizations.singular'))
                        ->default(fn (): ?int => app(OrganizationContext::class)->currentOrganizationId())
                        ->options(fn (): array => Organization::query()
                            ->select(['id', 'name'])
                            ->ordered()
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(fn (): bool => self::requiresOrganizationSelection())
                        ->visible(fn (): bool => self::requiresOrganizationSelection())
                        ->dehydratedWhenHidden()
                        ->live(),
                    $tenantId === null
                        ? Select::make('tenant_id')
                            ->label(__('admin.extra_charges.fields.tenant'))
                            ->options(fn (Get $get): array => self::tenantOptions($get('organization_id')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                        : Hidden::make('tenant_id')->default($tenantId),
                    $propertyId === null
                        ? Select::make('property_id')
                            ->label(__('admin.extra_charges.fields.property'))
                            ->options(fn (Get $get): array => self::propertyOptions($get('organization_id')))
                            ->searchable()
                            ->preload()
                            ->required()
                        : Hidden::make('property_id')->default($propertyId),
                    Select::make('billing_period_id')
                        ->label(__('admin.extra_charges.fields.billing_period'))
                        ->options(fn (Get $get): array => self::billingPeriodOptions($get('organization_id')))
                        ->searchable()
                        ->preload(),
                    Select::make('invoice_id')
                        ->label(__('admin.extra_charges.fields.invoice'))
                        ->options(fn (Get $get): array => self::invoiceOptions($get('organization_id'), $get('tenant_id'), $get('property_id')))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
            Section::make(__('admin.extra_charges.sections.charge'))
                ->schema([
                    Select::make('extra_charge_type_id')
                        ->label(__('admin.extra_charges.fields.extra_charge_type'))
                        ->options(fn (Get $get): array => self::chargeTypeOptions($get('organization_id')))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    TextInput::make('title')
                        ->label(__('admin.extra_charges.fields.title'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description_for_tenant')
                        ->label(__('admin.extra_charges.fields.description_for_tenant'))
                        ->rows(4)
                        ->required(fn (Get $get): bool => self::requiresTenantDescription($get('extra_charge_type_id')))
                        ->columnSpanFull(),
                    Textarea::make('internal_note')
                        ->label(__('admin.extra_charges.fields.internal_note'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('admin.extra_charges.sections.amounts'))
                ->schema([
                    TextInput::make('amount')
                        ->label(__('admin.extra_charges.fields.amount'))
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncTotal($get, $set)),
                    TextInput::make('currency')
                        ->label(__('admin.extra_charges.fields.currency'))
                        ->required()
                        ->default('EUR')
                        ->minLength(3)
                        ->maxLength(3),
                    TextInput::make('quantity')
                        ->label(__('admin.extra_charges.fields.quantity'))
                        ->numeric()
                        ->required()
                        ->default('1')
                        ->minValue(0.001)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncTotal($get, $set)),
                    TextInput::make('unit_price')
                        ->label(__('admin.extra_charges.fields.unit_price'))
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncTotal($get, $set)),
                    TextInput::make('tax_amount')
                        ->label(__('admin.extra_charges.fields.tax_amount'))
                        ->numeric()
                        ->default('0')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncTotal($get, $set)),
                    TextInput::make('total_amount')
                        ->label(__('admin.extra_charges.fields.total_amount'))
                        ->numeric()
                        ->required(),
                ])
                ->columns(3),
            Section::make(__('admin.extra_charges.sections.workflow'))
                ->schema([
                    Select::make('status')
                        ->label(__('admin.extra_charges.fields.status'))
                        ->options(ExtraChargeStatus::options())
                        ->default(ExtraChargeStatus::APPROVED->value)
                        ->required(),
                    Toggle::make('is_recurring')
                        ->label(__('admin.extra_charges.fields.is_recurring'))
                        ->default($recurringDefault ?? false),
                    DatePicker::make('starts_at')
                        ->label(__('admin.extra_charges.fields.starts_at')),
                    DatePicker::make('ends_at')
                        ->label(__('admin.extra_charges.fields.ends_at')),
                ])
                ->columns(2),
        ];
    }

    private static function requiresOrganizationSelection(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->isSuperadmin()
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }

    private static function resolvedOrganizationId(mixed $organizationId): ?int
    {
        if (is_numeric($organizationId)) {
            return (int) $organizationId;
        }

        $currentOrganizationId = app(OrganizationContext::class)->currentOrganizationId();

        return is_numeric($currentOrganizationId) ? (int) $currentOrganizationId : null;
    }

    /**
     * @return array<int, string>
     */
    private static function tenantOptions(mixed $organizationId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($resolvedOrganizationId)
            ->tenants()
            ->active()
            ->orderedByName()
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (User $tenant): array => [
                $tenant->id => filled($tenant->email) ? "{$tenant->name} · {$tenant->email}" : $tenant->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function propertyOptions(mixed $organizationId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
            ->forOrganization($resolvedOrganizationId)
            ->with(['building:id,organization_id,name'])
            ->ordered()
            ->limit(500)
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function chargeTypeOptions(mixed $organizationId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return ExtraChargeType::query()
            ->select(['id', 'organization_id', 'name', 'type', 'is_active'])
            ->forOrganization($resolvedOrganizationId)
            ->active()
            ->ordered()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function billingPeriodOptions(mixed $organizationId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return BillingPeriod::query()
            ->select(['id', 'organization_id', 'name', 'starts_at', 'ends_at'])
            ->forOrganization($resolvedOrganizationId)
            ->orderByDesc('starts_at')
            ->limit(120)
            ->get()
            ->mapWithKeys(fn (BillingPeriod $period): array => [
                $period->id => $period->name,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function invoiceOptions(mixed $organizationId, mixed $tenantId, mixed $propertyId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return Invoice::query()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'invoice_number', 'status'])
            ->forOrganization($resolvedOrganizationId)
            ->when(is_numeric($tenantId), fn ($query) => $query->forTenant((int) $tenantId))
            ->when(is_numeric($propertyId), fn ($query) => $query->forProperty((int) $propertyId))
            ->latestBillingFirst()
            ->limit(120)
            ->pluck('invoice_number', 'id')
            ->all();
    }

    private static function requiresTenantDescription(mixed $chargeTypeId): bool
    {
        if (! is_numeric($chargeTypeId)) {
            return true;
        }

        $type = ExtraChargeType::query()
            ->select(['id', 'tenant_visible_by_default', 'requires_comment'])
            ->find((int) $chargeTypeId);

        return ! $type instanceof ExtraChargeType
            || $type->tenant_visible_by_default
            || $type->requires_comment;
    }

    private static function syncTotal(Get $get, Set $set): void
    {
        $quantity = is_numeric($get('quantity')) ? (string) $get('quantity') : '1';
        $unitPrice = is_numeric($get('unit_price')) ? (string) $get('unit_price') : (string) ($get('amount') ?? '0');
        $taxAmount = is_numeric($get('tax_amount')) ? (string) $get('tax_amount') : '0';
        $calculator = app(UniversalBillingCalculator::class);

        $set('total_amount', $calculator->money(
            $calculator->add(
                $calculator->multiply($quantity, $unitPrice, 6),
                $taxAmount,
                6,
            ),
        ));
    }
}
