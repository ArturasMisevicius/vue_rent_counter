<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MeterType;
use App\Enums\TariffZone;
use App\Filament\Resources\MeterReadingResource\Pages;
use App\Filament\Resources\MeterReadingResource\RelationManagers;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Actions;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Filament resource for managing meter readings.
 *
 * Provides CRUD operations for meter readings with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility
 * - Monotonicity validation (Property 3)
 * - Zone support validation (Property 4)
 * - Relationship management (meters, properties)
 *
 * @see \App\Models\MeterReading
 * @see \App\Policies\MeterReadingPolicy
 */
class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-chart-bar';
    }

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.nav.readings');
    }

    // Integrate MeterReadingPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', MeterReading::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', MeterReading::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Visible to all authenticated users (Requirements 9.1, 9.2, 9.3)
    // Tenants can view meter readings for their properties
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('property_id')
                    ->label(__('meter_readings.labels.property'))
                    ->relationship('meter.property', 'address', function (Builder $query) {
                        // Filter properties by authenticated user's tenant_id (Requirement 9.1, 10.1, 12.4)
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);
                            
                            // For tenant users, filter by property_id as well
                            if ($user->role === \App\Enums\UserRole::TENANT && $user->property_id) {
                                $query->where('id', $user->property_id);
                            }
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('meter_id', null)),
                
                Forms\Components\Select::make('meter_id')
                    ->label(__('meter_readings.labels.meter'))
                    ->options(function (Get $get): Collection {
                        $query = Meter::query();
                        
                        // Filter by property if selected
                        if ($get('property_id')) {
                            $query->where('property_id', $get('property_id'));
                        }
                        
                        // Filter by authenticated user's tenant_id (Requirement 9.1, 10.1, 12.4)
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);
                        }
                        
                        return $query->get()
                            ->mapWithKeys(function (Meter $meter) {
                                $label = $meter->type->label();

                                return [$meter->id => "{$label} - {$meter->serial_number}"];
                            });
                    })
                    ->searchable()
                    ->required()
                    ->disabled(fn (Get $get): bool => !$get('property_id'))
                    ->helperText(__('meter_readings.helper_text.select_property_first'))
                    ->validationMessages([
                        'required' => __('meter_readings.validation.meter_id.required'),
                        'exists' => __('meter_readings.validation.meter_id.exists'),
                    ]),
                
                Forms\Components\DatePicker::make('reading_date')
                    ->label(__('meter_readings.labels.reading_date'))
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages([
                        'required' => __('meter_readings.validation.reading_date.required'),
                        'date' => __('meter_readings.validation.reading_date.date'),
                        'before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
                    ]),
                
                Forms\Components\TextInput::make('value')
                    ->label(__('meter_readings.labels.reading_value'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix(__('meter_readings.units'))
                    ->live(onBlur: true)
                    ->rules([
                        'required',
                        'numeric',
                        'min:0',
                        // Validation from StoreMeterReadingRequest and UpdateMeterReadingRequest
                        // Implements monotonicity validation (Property 3)
                        fn (Get $get, ?MeterReading $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            // Skip validation if value is not numeric or meter is not selected
                            if (!is_numeric($value)) {
                                return;
                            }
                            
                            $meterId = $get('meter_id');
                            if (!$meterId) {
                                return;
                            }

                            $meter = Meter::find($meterId);
                            if (!$meter) {
                                return;
                            }

                            $zone = $get('zone');
                            $service = app(\App\Services\MeterReadingService::class);

                            // For create operation (StoreMeterReadingRequest validation)
                            if (!$record) {
                                $previousReading = $service->getPreviousReading($meter, $zone);
                                
                                if ($previousReading && $value < $previousReading->value) {
                                    $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                        'previous' => $previousReading->value,
                                    ]));
                                }
                            } 
                            // For update operation (UpdateMeterReadingRequest validation)
                            else {
                                // Check against previous reading
                                $previousReading = $service->getAdjacentReading($record, $zone, 'previous');
                                if ($previousReading && $value < $previousReading->value) {
                                    $fail(__('meter_readings.validation.custom.monotonicity_lower', [
                                        'previous' => $previousReading->value,
                                    ]));
                                }

                                // Check against next reading
                                $nextReading = $service->getAdjacentReading($record, $zone, 'next');
                                if ($nextReading && $value > $nextReading->value) {
                                    $fail(__('meter_readings.validation.custom.monotonicity_higher', [
                                        'next' => $nextReading->value,
                                    ]));
                                }
                            }
                        },
                    ])
                    ->validationMessages([
                        'required' => __('meter_readings.validation.value.required'),
                        'numeric' => __('meter_readings.validation.value.numeric'),
                        'min' => __('meter_readings.validation.value.min'),
                    ]),
                
                Forms\Components\TextInput::make('zone')
                    ->label(__('meter_readings.labels.zone'))
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->helperText(__('meter_readings.helper_text.zone_optional'))
                    ->rules([
                        'nullable',
                        'string',
                        'max:50',
                        // Validation from StoreMeterReadingRequest
                        // Validates zone support (Property 4)
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $meterId = $get('meter_id');
                            if (!$meterId) {
                                return;
                            }

                            $meter = Meter::find($meterId);
                            if (!$meter) {
                                return;
                            }

                            // Zone provided but meter doesn't support zones
                            if ($value && !$meter->supports_zones) {
                                $fail(__('meter_readings.validation.custom.zone.unsupported'));
                            }

                            // Zone not provided but meter requires zones
                            if (!$value && $meter->supports_zones) {
                                $fail(__('meter_readings.validation.custom.zone.required_for_multi_zone'));
                            }
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('meter.property.address')
                    ->label(__('meter_readings.labels.property'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meter.type')
                    ->label(__('meter_readings.labels.meter_type'))
                    ->badge()
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label())
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reading_date')
                    ->label(__('meter_readings.labels.reading_date'))
                    ->date('Y-m-d')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label(__('meter_readings.labels.reading_value'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('consumption')
                    ->label(__('meter_readings.labels.consumption'))
                    ->getStateUsing(fn (MeterReading $record): ?string => 
                        $record->getConsumption() !== null 
                            ? number_format($record->getConsumption(), 2) 
                            : __('meter_readings.na')
                    )
                    ->sortable(false),
                
                Tables\Columns\TextColumn::make('zone')
                    ->label(__('meter_readings.labels.zone'))
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => $state ? (TariffZone::tryFrom($state)?->label() ?? $state) : '-')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('meter_readings.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('reading_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('meter_readings.labels.from_date'))
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('meter_readings.labels.until_date'))
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('reading_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('meter_readings.filters.indicator_from', [
                                'date' => \Carbon\Carbon::parse($data['from'])->toFormattedDateString(),
                            ]);
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('meter_readings.filters.indicator_until', [
                                'date' => \Carbon\Carbon::parse($data['until'])->toFormattedDateString(),
                            ]);
                        }
                        return $indicators;
                    }),
                
                SelectFilter::make('meter_type')
                    ->label(__('meter_readings.labels.meter_type'))
                    ->options(MeterType::labels())
                    ->query(function (Builder $query, $value): Builder {
                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas('meter', fn (Builder $meterQuery) => $meterQuery->where('type', $value));
                    })
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('meter_readings.modals.bulk_delete.title'))
                        ->modalDescription(__('meter_readings.modals.bulk_delete.description'))
                        ->modalSubmitActionLabel(__('meter_readings.modals.bulk_delete.confirm')),
                ]),
            ])
            ->defaultSort('reading_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
