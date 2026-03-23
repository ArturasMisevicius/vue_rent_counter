<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\PropertyType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.details'))
                    ->schema([
                        Select::make('organization_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->options(fn (): array => Organization::query()
                                ->forSuperadminControlPlane()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
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
                        Select::make('building_id')
                            ->label(__('admin.properties.fields.building'))
                            ->relationship(
                                name: 'building',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query, Get $get): Builder {
                                    $user = Auth::user();

                                    $query->select(['id', 'organization_id', 'name']);

                                    if ($user instanceof User && $user->isSuperadmin()) {
                                        return $query->where('organization_id', (int) ($get('organization_id') ?: -1));
                                    }

                                    return $query->where('organization_id', app(OrganizationContext::class)->currentOrganizationId());
                                },
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.properties.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit_number')
                            ->label(__('admin.properties.fields.unit_number'))
                            ->required()
                            ->maxLength(50),
                        Select::make('type')
                            ->label(__('admin.properties.fields.type'))
                            ->options(PropertyType::options())
                            ->required(),
                        TextInput::make('floor_area_sqm')
                            ->label(__('admin.properties.fields.floor_area_sqm'))
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(2),
            ]);
    }
}
