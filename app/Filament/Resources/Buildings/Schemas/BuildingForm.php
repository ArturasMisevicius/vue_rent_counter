<?php

namespace App\Filament\Resources\Buildings\Schemas;

use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Organization;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class BuildingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.buildings.sections.details'))
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
                            ->label(__('admin.buildings.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_1')
                            ->label(__('admin.buildings.fields.address_line_1'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_2')
                            ->label(__('admin.buildings.fields.address_line_2'))
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label(__('admin.buildings.fields.city'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label(__('admin.buildings.fields.postal_code'))
                            ->required()
                            ->maxLength(20),
                        TextInput::make('country_code')
                            ->label(__('admin.buildings.fields.country_code'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(2),
                    ])
                    ->columns(2),
            ]);
    }
}
