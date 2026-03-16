<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\UserRole;
use App\Filament\Tenant\Resources\MeterReadingResource\Pages;
use App\Models\MeterReading;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Meter Reading Resource for Tenant Panel
 * 
 * Allows tenants to view their meter readings and consumption history.
 * Read-only access - tenants cannot modify meter readings.
 */
final class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.my_property');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.meter_readings');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.meter_reading');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.meter_readings');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT && $user->property_id;
    }

    public static function canCreate(): bool
    {
        return false; // Tenants cannot create meter readings
    }

    public static function canEdit($record): bool
    {
        return false; // Tenants cannot edit meter readings
    }

    public static function canDelete($record): bool
    {
        return false; // Tenants cannot delete meter readings
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        return parent::getEloquentQuery()
            ->whereHas('meter.property', function (Builder $query) use ($user) {
                $query->where('id', $user?->property_id);
            })
            ->with([
                'meter.serviceConfiguration.utilityService',
                'meter.property',
            ])
            ->latest('reading_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.reading_details'))
                    ->schema([
                        Select::make('meter_id')
                            ->label(__('app.labels.meter'))
                            ->relationship('meter', 'name')
                            ->disabled(),
                        
                        TextInput::make('value')
                            ->label(__('app.labels.reading_value'))
                            ->numeric()
                            ->disabled(),
                        
                        DatePicker::make('reading_date')
                            ->label(__('app.labels.reading_date'))
                            ->disabled(),
                        
                        TextInput::make('consumption')
                            ->label(__('app.labels.consumption'))
                            ->numeric()
                            ->disabled(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.reading_details'))
                    ->schema([
                        TextEntry::make('meter.serviceConfiguration.utilityService.name')
                            ->label(__('app.labels.service')),
                        
                        TextEntry::make('meter.name')
                            ->label(__('app.labels.meter')),
                        
                        TextEntry::make('value')
                            ->label(__('app.labels.reading_value'))
                            ->suffix(fn ($record) => ' ' . ($record->meter->serviceConfiguration->utilityService->unit_of_measurement ?? '')),
                        
                        TextEntry::make('reading_date')
                            ->label(__('app.labels.reading_date'))
                            ->date(),
                        
                        TextEntry::make('consumption')
                            ->label(__('app.labels.consumption'))
                            ->suffix(fn ($record) => ' ' . ($record->meter->serviceConfiguration->utilityService->unit_of_measurement ?? ''))
                            ->placeholder(__('app.placeholders.calculated_automatically')),
                    ]),
                
                Section::make(__('app.sections.billing_information'))
                    ->schema([
                        TextEntry::make('billing_period_start')
                            ->label(__('app.labels.billing_period_start'))
                            ->date(),
                        
                        TextEntry::make('billing_period_end')
                            ->label(__('app.labels.billing_period_end'))
                            ->date(),
                        
                        TextEntry::make('created_at')
                            ->label(__('app.labels.recorded_at'))
                            ->dateTime(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.serviceConfiguration.utilityService.name')
                    ->label(__('app.labels.service'))
                    ->sortable(),
                
                TextColumn::make('meter.name')
                    ->label(__('app.labels.meter'))
                    ->searchable(),
                
                TextColumn::make('value')
                    ->label(__('app.labels.reading_value'))
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => ' ' . ($record->meter->serviceConfiguration->utilityService->unit_of_measurement ?? '')),
                
                TextColumn::make('consumption')
                    ->label(__('app.labels.consumption'))
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => ' ' . ($record->meter->serviceConfiguration->utilityService->unit_of_measurement ?? ''))
                    ->placeholder('—'),
                
                TextColumn::make('reading_date')
                    ->label(__('app.labels.reading_date'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('billing_period_start')
                    ->label(__('app.labels.billing_period'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('meter_id')
                    ->label(__('app.labels.meter'))
                    ->relationship('meter', 'name'),
                
                SelectFilter::make('service')
                    ->label(__('app.labels.service'))
                    ->options(function () {
                        $user = Auth::user();
                        if (!$user?->property_id) {
                            return [];
                        }
                        
                        return MeterReading::whereHas('meter.property', function (Builder $query) use ($user) {
                            $query->where('id', $user->property_id);
                        })
                        ->with('meter.serviceConfiguration.utilityService')
                        ->get()
                        ->pluck('meter.serviceConfiguration.utilityService.name', 'meter.serviceConfiguration.utilityService.id')
                        ->unique();
                    }),
            ])
            ->defaultSort('reading_date', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeterReadings::route('/'),
            'view' => Pages\ViewMeterReading::route('/{record}'),
        ];
    }
}