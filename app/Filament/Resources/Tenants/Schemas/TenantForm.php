<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tenants.sections.personal_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.tenants.fields.full_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.tenants.fields.email_address'))
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label(__('admin.tenants.fields.phone_number'))
                            ->maxLength(255),
                        Select::make('locale')
                            ->label(__('admin.tenants.fields.preferred_language'))
                            ->options(config('tenanto.locales', []))
                            ->required(),
                    ])
                    ->columns(2),
                Section::make(__('admin.tenants.sections.property_assignment'))
                    ->schema([
                        Select::make('property_id')
                            ->label(__('admin.tenants.fields.property'))
                            ->placeholder(__('admin.tenants.empty.no_assignment_yet'))
                            ->options(fn (): array => self::propertyOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $property = self::findProperty($state);

                                if ($property === null) {
                                    $set('unit_area_sqm', null);

                                    return;
                                }

                                $set('unit_area_sqm', $property->floor_area_sqm !== null
                                    ? (float) $property->floor_area_sqm
                                    : null);
                            })
                            ->default(fn (?User $record): ?int => $record?->currentPropertyAssignment?->property_id),
                        TextInput::make('unit_area_sqm')
                            ->label(__('admin.tenants.fields.unit_area_sqm'))
                            ->numeric()
                            ->minValue(0)
                            ->helperText(fn (Get $get): ?string => self::unitAreaHelperText($get('property_id')))
                            ->default(fn (?User $record): mixed => $record?->currentPropertyAssignment?->unit_area_sqm),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function propertyOptions(): array
    {
        $tenant = self::currentTenant();
        $organizationId = $tenant?->organization_id ?? app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return [];
        }

        return Property::query()
            ->availableForTenantAssignment($organizationId, $tenant?->id)
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private static function currentTenant(): ?User
    {
        $record = request()->route('record');

        if ($record instanceof User) {
            return $record;
        }

        if (! is_scalar($record) || blank($record)) {
            return null;
        }

        return User::query()
            ->select(['id', 'organization_id'])
            ->find($record);
    }

    private static function findProperty(mixed $propertyId): ?Property
    {
        if (blank($propertyId)) {
            return null;
        }

        $tenant = self::currentTenant();
        $organizationId = $tenant?->organization_id ?? app(OrganizationContext::class)->currentOrganizationId();

        if ($organizationId === null) {
            return null;
        }

        return Property::query()
            ->availableForTenantAssignment($organizationId, $tenant?->id)
            ->find($propertyId);
    }

    private static function unitAreaHelperText(mixed $propertyId): ?string
    {
        $property = self::findProperty($propertyId);

        if ($property?->floor_area_sqm === null) {
            return null;
        }

        return __('admin.tenants.messages.unit_area_defaults_to_property', [
            'area' => $property->areaDisplay(),
        ]);
    }
}
