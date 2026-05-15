<?php

namespace App\Filament\Resources\MeterReadings\Tables;

use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\MeterReadings\RejectMeterReadingAction;
use App\Filament\Actions\Admin\MeterReadings\ValidateMeterReadingAction;
use App\Filament\Resources\MeterReadings\MeterReadingResource;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MeterReadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('meter.name')
                    ->label(__('admin.meter_readings.columns.meter'))
                    ->state(fn (MeterReading $record): string => $record->meter?->displayName() ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.meter_readings.columns.property'))
                    ->state(fn (MeterReading $record): string => $record->property?->displayName() ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.reading_value'))
                    ->formatStateUsing(fn ($state): string => self::formatDecimal((float) $state, 3))
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
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->options(MeterReadingValidationStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forValidationStatusValue($data['value'] ?? null)),
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

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
