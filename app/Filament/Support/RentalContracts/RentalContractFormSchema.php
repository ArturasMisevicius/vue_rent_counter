<?php

namespace App\Filament\Support\RentalContracts;

use App\Enums\RentalContractStatus;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class RentalContractFormSchema
{
    /**
     * @return array<int, mixed>
     */
    public static function contract(?User $tenant = null, ?Property $property = null): array
    {
        $organizationId = self::organizationId($tenant, $property);

        return [
            Section::make(__('admin.rental_contracts.sections.contract_details'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('tenant_id')
                                ->label(__('admin.rental_contracts.fields.tenant'))
                                ->options(fn (): array => self::tenantOptions($organizationId))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($tenant?->id)
                                ->visible($tenant === null),
                            Select::make('property_id')
                                ->label(__('admin.rental_contracts.fields.property'))
                                ->options(fn (): array => self::propertyOptions($organizationId))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default($property?->id)
                                ->visible($property === null),
                            TextInput::make('contract_number')
                                ->label(__('admin.rental_contracts.fields.contract_number'))
                                ->required()
                                ->maxLength(255),
                            Select::make('status')
                                ->label(__('admin.rental_contracts.fields.status'))
                                ->options(RentalContractStatus::options())
                                ->default(RentalContractStatus::ACTIVE->value)
                                ->required(),
                        ]),
                    Grid::make(3)
                        ->schema([
                            DatePicker::make('start_date')
                                ->label(__('admin.rental_contracts.fields.start_date'))
                                ->required(),
                            DatePicker::make('end_date')
                                ->label(__('admin.rental_contracts.fields.end_date'))
                                ->required(),
                            DatePicker::make('signed_date')
                                ->label(__('admin.rental_contracts.fields.signed_date')),
                        ]),
                ]),
            Section::make(__('admin.rental_contracts.sections.financials'))
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('rent_amount')
                                ->label(__('admin.rental_contracts.fields.rent_amount'))
                                ->numeric()
                                ->minValue(0),
                            TextInput::make('deposit_amount')
                                ->label(__('admin.rental_contracts.fields.deposit_amount'))
                                ->numeric()
                                ->minValue(0),
                            TextInput::make('currency')
                                ->label(__('admin.rental_contracts.fields.currency'))
                                ->default('EUR')
                                ->maxLength(3)
                                ->required(),
                        ]),
                ]),
            Section::make(__('admin.rental_contracts.sections.visibility'))
                ->schema([
                    Toggle::make('tenant_visible')
                        ->label(__('admin.rental_contracts.fields.tenant_visible'))
                        ->default(false),
                    Textarea::make('tenant_visible_notes')
                        ->label(__('admin.rental_contracts.fields.tenant_visible_notes'))
                        ->rows(3)
                        ->maxLength(10000),
                    Textarea::make('internal_notes')
                        ->label(__('admin.rental_contracts.fields.internal_notes'))
                        ->rows(3)
                        ->maxLength(10000),
                ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function tenantOptions(?int $organizationId): array
    {
        if ($organizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->forOrganization($organizationId)
            ->tenants()
            ->orderedByName()
            ->get()
            ->mapWithKeys(fn (User $tenant): array => [
                $tenant->id => $tenant->name.' · '.$tenant->email,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function propertyOptions(?int $organizationId): array
    {
        if ($organizationId === null) {
            return [];
        }

        return Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
            ->forOrganization($organizationId)
            ->with(['building:id,organization_id,name'])
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private static function organizationId(?User $tenant, ?Property $property): ?int
    {
        return $tenant?->organization_id ?? $property?->organization_id;
    }
}
