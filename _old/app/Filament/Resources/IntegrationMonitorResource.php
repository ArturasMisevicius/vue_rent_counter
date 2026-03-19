<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationMonitorResource\Pages;
use App\Services\Integration\IntegrationResilienceHandler;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationMonitorResource extends Resource
{
    protected static ?string $model = null; // This is a virtual resource

    protected static ?string $navigationLabel = 'Integration Monitor';

    protected static ?int $navigationSort = 90;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-signal';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(self::getIntegrationStatusQuery())
            ->columns([
                Tables\Columns\TextColumn::make('service')
                    ->label('Service Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'closed' => 'success',
                        'half_open' => 'warning',
                        'open' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'closed' => 'heroicon-o-check-circle',
                        'half_open' => 'heroicon-o-exclamation-triangle',
                        'open' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('failure_count')
                    ->label('Failures')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('success_count')
                    ->label('Successes')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('open_since')
                    ->label('Open Since')
                    ->dateTime()
                    ->placeholder('N/A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'closed' => 'Healthy',
                        'half_open' => 'Recovering',
                        'open' => 'Failed',
                    ]),
            ])
            ->actions([
                Action::make('reset')
                    ->label('Reset Circuit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (array $data) {
                        // Reset circuit breaker logic would go here
                        // For now, just show a notification
                        Notification::make()
                            ->title('Circuit breaker reset')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('enable_offline')
                    ->label('Enable Offline Mode')
                    ->icon('heroicon-o-wifi')
                    ->color('warning')
                    ->action(function () {
                        // Stub: Enable offline mode
                        Notification::make()
                            ->title('Offline mode enabled')
                            ->warning()
                            ->send();
                    }),

                Action::make('disable_offline')
                    ->label('Disable Offline Mode')
                    ->icon('heroicon-o-signal')
                    ->color('success')
                    ->action(function () {
                        // Stub: Disable offline mode
                        Notification::make()
                            ->title('Offline mode disabled')
                            ->success()
                            ->send();
                    }),

                Action::make('sync_offline')
                    ->label('Sync Offline Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function () {
                        $results = app(IntegrationResilienceHandler::class)->synchronizeOfflineData('all');

                        Notification::make()
                            ->title('Synchronization completed')
                            ->body("Synchronized: {$results['synchronized']}, Errors: {$results['errors']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->poll('60s');
    }

    protected static function getIntegrationStatusQuery()
    {
        $resilienceHandler = app(IntegrationResilienceHandler::class);
        $healthStatus = $resilienceHandler->getServicesHealthStatus();

        return collect($healthStatus['services'])->map(function ($service) {
            return (object) $service;
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIntegrationMonitor::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
