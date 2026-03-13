<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\Property;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class ServiceDrillDownWidget extends BaseWidget
{
    protected static ?string $heading = 'Service Details';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('property.name')
                    ->label(__('dashboard.property'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('serviceConfiguration.utilityService.name')
                    ->label(__('dashboard.service'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Electricity' => 'warning',
                        'Water' => 'info',
                        'Heating' => 'danger',
                        'Gas' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('latest_reading')
                    ->label(__('dashboard.latest_reading'))
                    ->getStateUsing(function ($record) {
                        $latestReading = $record->readings()
                            ->latest()
                            ->first();
                        
                        if (!$latestReading) {
                            return __('dashboard.no_readings');
                        }

                        return number_format($latestReading->value, 2) . ' ' . 
                               ($record->serviceConfiguration->utilityService->unit_of_measurement ?? '');
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withMax('readings', 'value')
                            ->orderBy('readings_max_value', $direction);
                    }),

                Tables\Columns\TextColumn::make('monthly_consumption')
                    ->label(__('dashboard.monthly_consumption'))
                    ->getStateUsing(function ($record) {
                        $monthlyReadings = $record->readings()
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->sum('value');
                        
                        return number_format($monthlyReadings, 2) . ' ' . 
                               ($record->serviceConfiguration->utilityService->unit_of_measurement ?? '');
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withSum([
                            'readings' => function ($query) {
                                $query->whereMonth('created_at', now()->month)
                                      ->whereYear('created_at', now()->year);
                            }
                        ], 'value')
                        ->orderBy('readings_sum_value', $direction);
                    }),

                Tables\Columns\TextColumn::make('last_reading_date')
                    ->label(__('dashboard.last_reading_date'))
                    ->getStateUsing(function ($record) {
                        $latestReading = $record->readings()
                            ->latest()
                            ->first();
                        
                        return $latestReading?->created_at?->format('M j, Y') ?? __('dashboard.never');
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->withMax('readings', 'created_at')
                            ->orderBy('readings_max_created_at', $direction);
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('dashboard.status'))
                    ->getStateUsing(function ($record) {
                        $latestReading = $record->readings()
                            ->latest()
                            ->first();
                        
                        if (!$latestReading) {
                            return __('dashboard.no_data');
                        }

                        $daysSinceReading = $latestReading->created_at->diffInDays(now());
                        
                        return match (true) {
                            $daysSinceReading <= 7 => __('dashboard.current'),
                            $daysSinceReading <= 30 => __('dashboard.recent'),
                            default => __('dashboard.outdated'),
                        };
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $latestReading = $record->readings()
                            ->latest()
                            ->first();
                        
                        if (!$latestReading) {
                            return 'gray';
                        }

                        $daysSinceReading = $latestReading->created_at->diffInDays(now());
                        
                        return match (true) {
                            $daysSinceReading <= 7 => 'success',
                            $daysSinceReading <= 30 => 'warning',
                            default => 'danger',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service')
                    ->label(__('dashboard.service'))
                    ->relationship('serviceConfiguration.utilityService', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('property')
                    ->label(__('dashboard.property'))
                    ->relationship('property', 'name')
                    ->preload(),

                Tables\Filters\Filter::make('has_recent_readings')
                    ->label(__('dashboard.has_recent_readings'))
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('readings', function (Builder $query) {
                            $query->where('created_at', '>=', now()->subDays(30));
                        })
                    ),

                Tables\Filters\Filter::make('needs_reading')
                    ->label(__('dashboard.needs_reading'))
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDoesntHave('readings', function (Builder $query) {
                            $query->whereMonth('created_at', now()->month)
                                  ->whereYear('created_at', now()->year);
                        })
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view_readings')
                    ->label(__('dashboard.view_readings'))
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn ($record) => route('filament.tenant.resources.meter-readings.index', [
                        'tableFilters' => [
                            'meter' => ['value' => $record->id],
                        ],
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('add_reading')
                    ->label(__('dashboard.add_reading'))
                    ->icon('heroicon-o-plus')
                    ->url(fn ($record) => route('filament.tenant.resources.meter-readings.create', [
                        'meter_id' => $record->id,
                    ]))
                    ->visible(fn ($record) => Auth::user()?->can('create', \App\Models\MeterReading::class)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_readings')
                        ->label(__('dashboard.export_readings'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export functionality would be implemented here
                            // For now, just show a notification
                            \Filament\Notifications\Notification::make()
                                ->title(__('dashboard.export_started'))
                                ->body(__('dashboard.export_will_be_available_shortly'))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('property.name')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return \App\Models\Meter::query()->whereRaw('1 = 0'); // Empty query
        }

        return \App\Models\Meter::query()
            ->whereHas('property', function (Builder $query) use ($user) {
                $query->where('tenant_id', $user->currentTeam->id);
            })
            ->with([
                'property',
                'serviceConfiguration.utilityService',
                'readings' => function ($query) {
                    $query->latest()->limit(1);
                },
            ]);
    }
}