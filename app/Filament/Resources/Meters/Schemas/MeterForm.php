<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\UnitOfMeasurement;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MeterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meters.sections.details'))
                    ->schema([
                        Select::make('organization_scope_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->options(fn (): array => Organization::query()
                                ->forSuperadminControlPlane()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn (): bool => self::requiresOrganizationScopeFilters())
                            ->dehydrated(false)
                            ->visibleOn('create')
                            ->visible(fn (): bool => self::requiresOrganizationScopeFilters())
                            ->afterStateUpdated(function (Set $set): void {
                                $set('building_scope_id', null);
                                $set('property_id', null);
                            }),
                        Select::make('building_scope_id')
                            ->label(__('admin.meters.fields.building'))
                            ->options(fn (Get $get): array => self::buildingOptions($get('organization_scope_id')))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->visibleOn('create')
                            ->visible(fn (): bool => self::requiresOrganizationScopeFilters())
                            ->afterStateUpdated(function (Set $set): void {
                                $set('property_id', null);
                            }),
                        Select::make('property_id')
                            ->label(__('admin.meters.fields.property'))
                            ->relationship(
                                name: 'property',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Get $get): Builder {
                                    $query->select(['id', 'organization_id', 'building_id', 'name', 'unit_number']);

                                    $organizationId = self::resolvedPropertyOrganizationId($get('organization_scope_id'));

                                    if ($organizationId === null) {
                                        return $query->whereKey(-1);
                                    }

                                    $query->where('organization_id', $organizationId);

                                    $buildingId = (int) ($get('building_scope_id') ?: 0);

                                    if ($buildingId > 0) {
                                        $query->where('building_id', $buildingId);
                                    }

                                    return $query;
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.meters.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('identifier')
                            ->label(__('admin.meters.fields.identifier'))
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label(__('admin.meters.fields.type'))
                            ->options(MeterType::options())
                            ->required(),
                        Select::make('unit')
                            ->label(__('admin.meters.fields.unit'))
                            ->options(UnitOfMeasurement::options())
                            ->placeholder(__('admin.meters.fields.unit_placeholder')),
                        Select::make('status')
                            ->label(__('admin.meters.fields.status'))
                            ->options(MeterStatus::options())
                            ->default(MeterStatus::ACTIVE->value)
                            ->required(),
                        DatePicker::make('installed_at')
                            ->label(__('admin.meters.fields.installed_at')),
                    ])
                    ->columns(2),
            ]);
    }

    private static function requiresOrganizationScopeFilters(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperadmin()
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }

    /**
     * @return array<int, string>
     */
    private static function buildingOptions(mixed $organizationScopeId): array
    {
        $organizationId = self::resolvedPropertyOrganizationId($organizationScopeId);

        if ($organizationId === null) {
            return [];
        }

        return Building::query()
            ->select(['id', 'organization_id', 'name'])
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function resolvedPropertyOrganizationId(mixed $organizationScopeId): ?int
    {
        if (! self::requiresOrganizationScopeFilters()) {
            return app(OrganizationContext::class)->currentOrganizationId();
        }

        if (blank($organizationScopeId)) {
            return null;
        }

        return (int) $organizationScopeId;
    }
}
