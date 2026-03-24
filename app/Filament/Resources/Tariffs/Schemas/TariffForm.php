<?php

namespace App\Filament\Resources\Tariffs\Schemas;

use App\Enums\TariffType;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TariffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tariffs.sections.details'))
                    ->schema([
                        Select::make('provider_id')
                            ->label(__('admin.tariffs.fields.provider'))
                            ->relationship(
                                name: 'provider',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'organization_id', 'name', 'service_type']);

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
                            ->label(__('admin.tariffs.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('remote_id')
                            ->label(__('admin.tariffs.fields.remote_id'))
                            ->maxLength(255),
                        Select::make('configuration.type')
                            ->label(__('admin.tariffs.fields.type'))
                            ->options(TariffType::options())
                            ->required(),
                        TextInput::make('configuration.currency')
                            ->label(__('admin.tariffs.fields.currency'))
                            ->required()
                            ->maxLength(10),
                        TextInput::make('configuration.rate')
                            ->label(__('admin.tariffs.fields.rate'))
                            ->numeric(),
                        DateTimePicker::make('active_from')
                            ->label(__('admin.tariffs.fields.active_from'))
                            ->required(),
                        DateTimePicker::make('active_until')
                            ->label(__('admin.tariffs.fields.active_until')),
                    ])
                    ->columns(2),
            ]);
    }
}
