<?php

namespace App\Filament\Resources\MeterReadings\Tables;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\MeterReadings\RejectMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\ValidateMeterReadingAction;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Models\MeterReading;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MeterReadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('admin.meter_readings.columns.meter'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.meter_readings.columns.property'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.reading_value'))
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 3, '.', ''), '0'), '.'))
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label(__('admin.meter_readings.columns.reading_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->badge(),
                TextColumn::make('submission_method')
                    ->label(__('admin.meter_readings.columns.submission_method'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->options(MeterReadingValidationStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('validate')
                    ->label(__('admin.meter_readings.actions.validate'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (MeterReading $record): bool => $record->validation_status === MeterReadingValidationStatus::PENDING)
                    ->authorize(fn (MeterReading $record): bool => MeterReadingResource::canEdit($record))
                    ->action(function (MeterReading $record, ValidateMeterReadingAction $validateMeterReadingAction): void {
                        $validateMeterReadingAction->handle($record);

                        Notification::make()
                            ->title(__('admin.meter_readings.messages.validated'))
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('admin.meter_readings.actions.reject'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (MeterReading $record): bool => $record->validation_status === MeterReadingValidationStatus::PENDING)
                    ->authorize(fn (MeterReading $record): bool => MeterReadingResource::canEdit($record))
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('admin.meter_readings.fields.rejection_reason'))
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (MeterReading $record, array $data, RejectMeterReadingAction $rejectMeterReadingAction): void {
                        $rejectMeterReadingAction->handle($record, $data);

                        Notification::make()
                            ->title(__('admin.meter_readings.messages.rejected'))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('reading_date', 'desc');
    }
}
