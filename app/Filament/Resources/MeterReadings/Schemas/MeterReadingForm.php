<?php

namespace App\Filament\Resources\MeterReadings\Schemas;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Support\Admin\OrganizationContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
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
