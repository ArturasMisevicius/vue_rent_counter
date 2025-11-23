<?php

namespace App\Filament\Resources;

use App\Enums\MeterType;
use App\Enums\TariffZone;
use App\Filament\Resources\MeterReadingResource\Pages;
use App\Filament\Resources\MeterReadingResource\RelationManagers;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Meter Readings';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('property_id')
                    ->label('Property')
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
                    ->label('Meter')
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
                    ->helperText('Select a property first')
                    ->validationMessages([
                        'required' => 'Meter is required',
                        'exists' => 'Selected meter does not exist',
                    ]),
                
                Forms\Components\DatePicker::make('reading_date')
                    ->label('Reading Date')
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages([
                        'required' => 'Reading date is required',
                        'date' => 'Reading date must be a valid date',
                        'before_or_equal' => 'Reading date cannot be in the future',
                    ]),
                
                Forms\Components\TextInput::make('value')
                    ->label('Reading Value')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('units')
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
                                    $fail("Reading cannot be lower than previous reading ({$previousReading->value})");
                                }
                            } 
                            // For update operation (UpdateMeterReadingRequest validation)
                            else {
                                // Check against previous reading
                                $previousReading = $service->getAdjacentReading($record, $zone, 'previous');
                                if ($previousReading && $value < $previousReading->value) {
                                    $fail("Reading cannot be lower than previous reading ({$previousReading->value})");
                                }

                                // Check against next reading
                                $nextReading = $service->getAdjacentReading($record, $zone, 'next');
                                if ($nextReading && $value > $nextReading->value) {
                                    $fail("Reading cannot be higher than next reading ({$nextReading->value})");
                                }
                            }
                        },
                    ])
                    ->validationMessages([
                        'required' => 'Meter reading is required',
                        'numeric' => 'Reading must be a number',
                        'min' => 'Reading must be a positive number',
                    ]),
                
                Forms\Components\TextInput::make('zone')
                    ->label('Zone')
                    ->maxLength(50)
                    ->live(onBlur: true)
                    ->helperText('Optional: For multi-zone meters (e.g., day/night)')
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
                                $fail('This meter does not support zone-based readings');
                            }

                            // Zone not provided but meter requires zones
                            if (!$value && $meter->supports_zones) {
                                $fail('Zone is required for meters that support multiple zones');
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
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meter.type')
                    ->label('Meter Type')
                    ->badge()
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label())
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reading_date')
                    ->label('Reading Date')
                    ->date('Y-m-d')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('value')
                    ->label('Reading Value')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('consumption')
                    ->label('Consumption')
                    ->getStateUsing(fn (MeterReading $record): ?string => 
                        $record->getConsumption() !== null 
                            ? number_format($record->getConsumption(), 2) 
                            : 'N/A'
                    )
                    ->sortable(false),
                
                Tables\Columns\TextColumn::make('zone')
                    ->label('Zone')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => $state ? (TariffZone::tryFrom($state)?->label() ?? $state) : '-')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('reading_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date')
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
                            $indicators[] = 'From: ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until: ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                
                Tables\Filters\SelectFilter::make('meter.type')
                    ->label('Meter Type')
                    ->relationship('meter', 'type')
                    ->options(MeterType::labels())
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Meter Readings')
                        ->modalDescription('Are you sure you want to delete these meter readings? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
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
