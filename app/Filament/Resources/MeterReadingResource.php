<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Filament\Resources\MeterReadingResource\Pages;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\MeterReadingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Utilities Management';

    protected static ?int $navigationSort = 4;

    /**
     * Determine if navigation should be registered.
     *
     * Hides meter readings from Manager role in global navigation to simplify
     * their interface. Managers can still access meter readings through:
     * - Building context (via relation managers)
     * - Property context (via relation managers)
     * - Dashboard shortcuts
     *
     * Navigation visibility by role:
     * - Superadmin: ✅ Visible
     * - Admin: ✅ Visible
     * - Manager: ❌ Hidden (access via context only)
     * - Tenant: ❌ Hidden
     *
     * @return bool True if navigation should be visible, false otherwise
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        
        if (!$user instanceof User) {
            return false;
        }
        
        // Hide from Manager role in global navigation
        if ($user->role === UserRole::MANAGER) {
            return false;
        }
        
        return $user->can('viewAny', MeterReading::class);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Reading Information')
                    ->schema([
                        Forms\Components\Select::make('meter_id')
                            ->label('Meter')
                            ->relationship('meter', 'serial_number')
                            ->getOptionLabelFromRecordUsing(function (Meter $meter): string {
                                $property = $meter->property;

                                if (! $property) {
                                    return $meter->serial_number;
                                }

                                $buildingLabel = $property->building?->display_name ?? $property->address;
                                $unitLabel = filled($property->unit_number) ? $property->unit_number : '—';

                                return "{$meter->serial_number} ({$buildingLabel} - {$unitLabel})";
                            })
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\DatePicker::make('reading_date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\TextInput::make('value')
                            ->label('Reading Value')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->rule(function (Get $get, ?MeterReading $record): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                                    $meterId = $get('meter_id');

                                    if (! $meterId) {
                                        return;
                                    }

                                    $meter = Meter::find($meterId);

                                    if (! $meter) {
                                        return;
                                    }

                                    $service = app(MeterReadingService::class);

                                    if (! is_numeric($value)) {
                                        return;
                                    }

                                    $readingDate = $get('reading_date');
                                    $readingDateString = $readingDate instanceof \DateTimeInterface
                                        ? $readingDate->format('Y-m-d')
                                        : (is_string($readingDate) && $readingDate !== '' ? $readingDate : null);

                                    $zone = $get('zone');
                                    $zone = is_string($zone) && $zone !== '' ? $zone : null;

                                    if ($record instanceof MeterReading && $record->exists) {
                                        $candidate = new MeterReading();
                                        $candidate->id = $record->id;
                                        $candidate->setRelation('meter', $meter);
                                        $candidate->reading_date = $readingDateString ? Carbon::parse($readingDateString) : $record->reading_date;
                                        $candidate->zone = $zone;

                                        $previousReading = $service->getAdjacentReading($candidate, $zone, 'previous');

                                        if ($previousReading && (float) $value < (float) $previousReading->value) {
                                            $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                                'previous' => $previousReading->value,
                                            ]));
                                        }

                                        $nextReading = $service->getAdjacentReading($candidate, $zone, 'next');

                                        if ($nextReading && (float) $value > (float) $nextReading->value) {
                                            $fail(__('meter_readings.validation.custom.monotonicity_higher', [
                                                'next' => $nextReading->value,
                                            ]));
                                        }

                                        return;
                                    }

                                    if ($readingDateString) {
                                        $previousReading = $service->getPreviousReading($meter, $zone, $readingDateString);

                                        if ($previousReading && (float) $value < (float) $previousReading->value) {
                                            $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                                'previous' => $previousReading->value,
                                            ]));
                                        }

                                        $nextReading = $service->getNextReading($meter, $zone, $readingDateString);

                                        if ($nextReading && (float) $value > (float) $nextReading->value) {
                                            $fail(__('meter_readings.validation.custom.monotonicity_higher', [
                                                'next' => $nextReading->value,
                                            ]));
                                        }

                                        return;
                                    }

                                    $previousReading = $service->getPreviousReading($meter, $zone);

                                    if ($previousReading && (float) $value < (float) $previousReading->value) {
                                        $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                            'previous' => $previousReading->value,
                                        ]));
                                    }
                                };
                            }),

                        Forms\Components\TextInput::make('zone')
                            ->label('Tariff Zone')
                            ->maxLength(50)
                            ->placeholder('e.g., day, night')
                            ->helperText('Leave empty for single-zone meters')
                            ->rule(function (Get $get): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    $meterId = $get('meter_id');

                                    if (! $meterId) {
                                        return;
                                    }

                                    $meter = Meter::find($meterId);

                                    if (! $meter) {
                                        return;
                                    }

                                    $zone = is_string($value) && $value !== '' ? $value : null;

                                    if ($zone && ! $meter->supports_zones) {
                                        $fail(__('meter_readings.validation.custom.zone.unsupported'));
                                    }

                                    if (! $zone && $meter->supports_zones) {
                                        $fail(__('meter_readings.validation.custom.zone.required_for_multi_zone'));
                                    }
                                };
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('meter.serial_number')
                    ->label('Meter')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('meter.property.building.display_name')
                    ->label('Building')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('meter.property.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('meter.type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof MeterType ? $state->label() : ucfirst(str_replace('_', ' ', (string) $state)))
                    ->color(fn ($state): string => match ($state instanceof MeterType ? $state->value : (string) $state) {
                        'electricity' => 'warning',
                        'water_cold' => 'info',
                        'water_hot' => 'danger',
                        'heating' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reading_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Reading')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn (MeterReading $record) => $record->meter ? (' ' . $record->meter->getUnitOfMeasurement()) : ''),

                Tables\Columns\TextColumn::make('zone')
                    ->badge()
                    ->placeholder('Single Zone'),

                Tables\Columns\TextColumn::make('validation_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ValidationStatus ? $state->getLabel() : ucfirst(str_replace('_', ' ', (string) $state)))
                    ->color(fn ($state): string => match ($state instanceof ValidationStatus ? $state->value : (string) $state) {
                        'validated' => 'success',
                        'pending' => 'warning',
                        'requires_review' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('enteredBy.name')
                    ->label('Entered By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meter_id')
                    ->label('Meter')
                    ->relationship('meter', 'serial_number')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('reading_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Truth-but-Verify Workflow Actions (Gold Master v7.0)
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MeterReading $record): bool => 
                        $record->validation_status === ValidationStatus::PENDING || 
                        $record->validation_status === ValidationStatus::REQUIRES_REVIEW
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Reading')
                    ->modalDescription('Are you sure you want to approve this meter reading?')
                    ->action(function (MeterReading $record): void {
                        $record->markAsValidated(auth()->id());
                        
                        Notification::make()
                            ->title('Reading Approved')
                            ->body('The meter reading has been approved and is now available for billing.')
                            ->success()
                            ->send();
                    }),
                
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MeterReading $record): bool => 
                        $record->validation_status === ValidationStatus::PENDING || 
                        $record->validation_status === ValidationStatus::REQUIRES_REVIEW
                    )
                    ->form([
                        Forms\Components\Textarea::make('validation_notes')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide a reason for rejecting this reading...')
                            ->rows(3),
                    ])
                    ->modalHeading('Reject Reading')
                    ->modalDescription('Please provide a reason for rejecting this meter reading.')
                    ->action(function (MeterReading $record, array $data): void {
                        $record->validation_notes = $data['validation_notes'];
                        $record->markAsRejected(auth()->id());
                        
                        Notification::make()
                            ->title('Reading Rejected')
                            ->body('The meter reading has been rejected and marked for review.')
                            ->warning()
                            ->send();
                    }),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('reading_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeterReadings::route('/'),
            'create' => Pages\CreateMeterReading::route('/create'),
            'edit' => Pages\EditMeterReading::route('/{record}/edit'),
        ];
    }
}
