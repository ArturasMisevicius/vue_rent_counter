<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\IntegrationStatus;
use App\Filament\Resources\IntegrationHealthResource\Pages;
use App\Models\IntegrationHealthCheck;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament resource for managing integration health monitoring.
 * 
 * Provides CRUD interface for viewing integration health checks,
 * monitoring service status, and managing integration configurations.
 * 
 * @package App\Filament\Resources
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class IntegrationHealthResource extends Resource
{
    protected static ?string $model = IntegrationHealthCheck::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'service_name';

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.integration_health');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.integration_health_check');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.integration_health_checks');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.service_details'))
                    ->schema([
                        TextInput::make('service_name')
                            ->label(__('app.labels.service_name'))
                            ->required()
                            ->maxLength(100)
                            ->placeholder(__('app.placeholders.service_name')),

                        TextInput::make('endpoint')
                            ->label(__('app.labels.endpoint'))
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->placeholder(__('app.placeholders.endpoint_url')),

                        Select::make('status')
                            ->label(__('app.labels.status'))
                            ->required()
                            ->options(collect(IntegrationStatus::cases())->mapWithKeys(
                                fn(IntegrationStatus $status) => [$status->value => $status->getLabel()]
                            ))
                            ->default(IntegrationStatus::UNKNOWN->value),
                    ])
                    ->columns(2),

                Section::make(__('app.sections.health_metrics'))
                    ->schema([
                        TextInput::make('response_time_ms')
                            ->label(__('app.labels.response_time_ms'))
                            ->numeric()
                            ->suffix('ms')
                            ->placeholder(__('app.placeholders.response_time')),

                        Textarea::make('error_message')
                            ->label(__('app.labels.error_message'))
                            ->rows(3)
                            ->placeholder(__('app.placeholders.error_message')),

                        DateTimePicker::make('checked_at')
                            ->label(__('app.labels.checked_at'))
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name')
                    ->label(__('app.labels.service_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('endpoint')
                    ->label(__('app.labels.endpoint'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->endpoint)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (IntegrationStatus $state): string => $state->getColor())
                    ->icon(fn (IntegrationStatus $state): string => $state->getIcon())
                    ->formatStateUsing(fn (IntegrationStatus $state): string => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('response_time_ms')
                    ->label(__('app.labels.response_time'))
                    ->formatStateUsing(function (?int $state): string {
                        if ($state === null) {
                            return '—';
                        }
                        
                        $color = match (true) {
                            $state < 100 => 'text-success-600',
                            $state < 500 => 'text-warning-600',
                            default => 'text-danger-600',
                        };
                        
                        return "<span class=\"{$color}\">{$state}ms</span>";
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('checked_at')
                    ->label(__('app.labels.last_check'))
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record): string => 
                        $record->checked_at->format('l, F j, Y')
                    ),

                Tables\Columns\TextColumn::make('error_message')
                    ->label(__('app.labels.error'))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->placeholder(__('app.placeholders.no_errors'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(collect(IntegrationStatus::cases())->mapWithKeys(
                        fn(IntegrationStatus $status) => [$status->value => $status->getLabel()]
                    ))
                    ->multiple(),

                Tables\Filters\Filter::make('recent')
                    ->label(__('app.filters.recent_checks'))
                    ->query(fn (Builder $query): Builder => 
                        $query->where('checked_at', '>=', now()->subHours(24))
                    )
                    ->default(),

                Tables\Filters\Filter::make('unhealthy')
                    ->label(__('app.filters.unhealthy_only'))
                    ->query(fn (Builder $query): Builder => 
                        $query->whereIn('status', [
                            IntegrationStatus::UNHEALTHY,
                            IntegrationStatus::CIRCUIT_OPEN,
                        ])
                    ),

                Tables\Filters\Filter::make('slow_response')
                    ->label(__('app.filters.slow_response'))
                    ->query(fn (Builder $query): Builder => 
                        $query->where('response_time_ms', '>', 1000)
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('health_check')
                    ->label(__('app.actions.run_health_check'))
                    ->icon('heroicon-o-heart')
                    ->color('info')
                    ->action(function (IntegrationHealthCheck $record) {
                        $resilienceHandler = app(\App\Services\Integration\IntegrationResilienceHandler::class);
                        
                        try {
                            $result = $resilienceHandler->performHealthCheck($record->service_name);
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.health_check_completed'))
                                ->body(__('integration.health.check_completed', ['service' => $record->service_name]))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.health_check_failed'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('app.modals.run_health_check'))
                    ->modalDescription(__('app.modals.health_check_description')),

                Tables\Actions\Action::make('enable_maintenance')
                    ->label(__('app.actions.enable_maintenance'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->form([
                        TextInput::make('duration_minutes')
                            ->label(__('app.labels.duration_minutes'))
                            ->required()
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->maxValue(1440),
                        
                        Textarea::make('reason')
                            ->label(__('app.labels.reason'))
                            ->placeholder(__('app.placeholders.maintenance_reason'))
                            ->rows(2),
                    ])
                    ->action(function (IntegrationHealthCheck $record, array $data) {
                        $resilienceHandler = app(\App\Services\Integration\IntegrationResilienceHandler::class);
                        
                        $resilienceHandler->enableMaintenanceMode(
                            $record->service_name,
                            $data['duration_minutes']
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title(__('app.notifications.maintenance_enabled'))
                            ->body(__('integration.maintenance.enabled', ['service' => $record->service_name]))
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (IntegrationHealthCheck $record) => 
                        !app(\App\Services\Integration\IntegrationResilienceHandler::class)
                            ->isInMaintenanceMode($record->service_name)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('bulk_health_check')
                        ->label(__('app.actions.run_health_checks'))
                        ->icon('heroicon-o-heart')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $resilienceHandler = app(\App\Services\Integration\IntegrationResilienceHandler::class);
                            $successCount = 0;
                            $errorCount = 0;
                            
                            foreach ($records as $record) {
                                try {
                                    $resilienceHandler->performHealthCheck($record->service_name);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $errorCount++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('app.notifications.bulk_health_check_completed'))
                                ->body(__('app.notifications.health_check_results', [
                                    'success' => $successCount,
                                    'errors' => $errorCount,
                                ]))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('checked_at', 'desc')
            ->poll('60s')
            ->deferLoading()
            ->striped();
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
            'index' => Pages\ListIntegrationHealthChecks::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->latest('checked_at');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', IntegrationHealthCheck::class);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', IntegrationHealthCheck::class);
    }

    public static function canView($record): bool
    {
        return auth()->user()->can('view', $record);
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete', $record);
    }

    public static function getNavigationBadge(): ?string
    {
        $unhealthyCount = IntegrationHealthCheck::whereIn('status', [
            IntegrationStatus::UNHEALTHY,
            IntegrationStatus::CIRCUIT_OPEN,
        ])->count();

        return $unhealthyCount > 0 ? (string) $unhealthyCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unhealthyCount = IntegrationHealthCheck::whereIn('status', [
            IntegrationStatus::UNHEALTHY,
            IntegrationStatus::CIRCUIT_OPEN,
        ])->count();

        return $unhealthyCount > 0 ? 'danger' : null;
    }
}
