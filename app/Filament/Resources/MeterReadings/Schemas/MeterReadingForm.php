<?php

namespace App\Filament\Resources\MeterReadings\Schemas;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MeterReadingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meter_readings.sections.details'))
                    ->schema([
                        Select::make('meter_id')
                            ->label(__('admin.meter_readings.fields.meter'))
                            ->relationship(
                                name: 'meter',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    $query->select(['id', 'organization_id', 'property_id', 'name', 'identifier']);

                                    $organizationId = app(OrganizationContext::class)->currentOrganizationId();
                                    $user = Auth::user();

                                    if ($organizationId === null && $user instanceof User && $user->isSuperadmin()) {
                                        return $query;
                                    }

                                    return $query->where('organization_id', $organizationId);
                                },
                            )
                            ->default(fn (): ?int => request()->integer('meter') ?: null)
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('reading_value')
                            ->label(__('admin.meter_readings.fields.reading_value'))
                            ->numeric()
                            ->required(),
                        DatePicker::make('reading_date')
                            ->label(__('admin.meter_readings.fields.reading_date'))
                            ->required(),
                        Select::make('submission_method')
                            ->label(__('admin.meter_readings.fields.submission_method'))
                            ->options(MeterReadingSubmissionMethod::options())
                            ->default(MeterReadingSubmissionMethod::ADMIN_MANUAL->value)
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('admin.meter_readings.fields.notes'))
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }
}
