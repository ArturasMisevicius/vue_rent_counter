<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\ServiceConfiguration;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MetersRelationManager extends RelationManager
{
    protected static string $relationship = 'meters';

    protected static ?string $recordTitleAttribute = 'serial_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('service_configuration_id')
                    ->label('Service')
                    ->options(function (): array {
                        $propertyId = $this->getOwnerRecord()->id;

                        return ServiceConfiguration::query()
                            ->where('property_id', $propertyId)
                            ->where('is_active', true)
                            ->with('utilityService:id,name,unit_of_measurement')
                            ->orderBy('effective_from', 'desc')
                            ->get()
                            ->mapWithKeys(function (ServiceConfiguration $config): array {
                                $service = $config->utilityService;

                                $label = $service
                                    ? "{$service->name} ({$service->unit_of_measurement})"
                                    : "Service #{$config->id}";

                                return [$config->id => $label];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (!$state) {
                            return;
                        }

                        $set('type', MeterType::CUSTOM->value);

                        $config = ServiceConfiguration::with('utilityService')->find($state);
                        if ($config?->pricing_model === \App\Enums\PricingModel::TIME_OF_USE) {
                            $set('supports_zones', true);
                        }
                    })
                    ->helperText('Link this meter to a configured service for billing.'),

                Forms\Components\Select::make('type')
                    ->label(__('meters.relation.meter_type'))
                    ->options(MeterType::class)
                    ->required()
                    ->native(false)
                    ->default(MeterType::CUSTOM->value)
                    ->disabled(fn (Get $get): bool => filled($get('service_configuration_id')))
                    ->dehydrated(fn (?string $state): bool => true),
                
                Forms\Components\TextInput::make('serial_number')
                    ->label(__('meters.relation.serial_number'))
                    ->required()
                    ->maxLength(255)
                    ->unique(Meter::class, 'serial_number', ignoreRecord: true),
                
                Forms\Components\DatePicker::make('installation_date')
                    ->label(__('meters.relation.installation_date'))
                    ->required()
                    ->native(false)
                    ->maxDate(now()),

                Forms\Components\Toggle::make('supports_zones')
                    ->label(__('meters.labels.supports_zones'))
                    ->inline(false)
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('serial_number')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label(__('meters.relation.type'))
                    ->badge()
                    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label()),
                
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('meters.relation.serial_number'))
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('installation_date')
                    ->label(__('meters.relation.installed'))
                    ->date()
                    ->sortable(),

                Tables\Columns\IconColumn::make('supports_zones')
                    ->label(__('meters.labels.supports_zones'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('serviceConfiguration.utilityService.name')
                    ->label('Service')
                    ->toggleable()
                    ->placeholder(__('app.common.na')),
                
                Tables\Columns\TextColumn::make('readings_count')
                    ->label(__('meters.relation.readings'))
                    ->counts('readings')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('meters.relation.meter_type'))
                    ->options(MeterType::labels())
                    ->native(false),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Ensure tenant_id is set from property
                        $data['tenant_id'] = $this->getOwnerRecord()->tenant_id;
                        
                        return $data;
                    }),
            ])
            ->recordActions([
                Actions\Action::make('viewReadings')
                    ->label(__('meters.actions.view_readings'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->url(fn (Meter $record): string => 
                        \App\Filament\Resources\MeterReadingResource::getUrl('index', [
                            'tableFilters' => [
                                'meter_id' => ['value' => $record->id]
                            ]
                        ])
                    )
                    ->visible(fn (): bool => auth()->user()->can('viewAny', \App\Models\MeterReading::class))
                    ->openUrlInNewTab(false),
                    
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('meters.relation.empty_heading'))
            ->emptyStateDescription(__('meters.relation.empty_description'))
            ->emptyStateActions([
                Actions\CreateAction::make()
                    ->label(__('meters.relation.add_first')),
            ]);
    }
}
