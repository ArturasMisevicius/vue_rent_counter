<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                        Select::make('property_id')
                            ->label(__('admin.meters.fields.property'))
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
                        TextInput::make('unit')
                            ->label(__('admin.meters.fields.unit'))
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
}
