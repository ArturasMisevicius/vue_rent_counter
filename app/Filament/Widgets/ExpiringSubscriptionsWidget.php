<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Services\QueryOptimizationService;
use App\Models\Subscription;
use Filament\Tables\Actions\Action;

/**
 * Expiring Subscriptions Widget with optimized loading
 * 
 * Shows subscriptions expiring within 14 days with quick renewal actions
 */
class ExpiringSubscriptionsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;
    
    // Polling interval - refresh every 5 minutes
    protected ?string $pollingInterval = '300s';
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $queryService = app(QueryOptimizationService::class);
        
        return $table
            ->query(
                // Use optimized service method
                $queryService->getExpiringSubscriptions(14)->toQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.organization.name')
                    ->label('Organization')
                    ->limit(25)
                    ->searchable()
                    ->sortable()
                    ->tooltip(function (Subscription $record): ?string {
                        return $record->user?->organization?->name;
                    }),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin User')
                    ->limit(20)
                    ->searchable()
                    ->tooltip(function (Subscription $record): ?string {
                        return $record->user?->name . ' (' . $record->user?->email . ')';
                    }),
                    
                Tables\Columns\BadgeColumn::make('plan_type')
                    ->label('Plan')
                    ->colors([
                        'success' => 'enterprise',
                        'warning' => 'professional',
                        'danger' => 'basic',
                    ]),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'expired',
                    ]),
                    
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(function (Subscription $record): string {
                        $daysUntilExpiry = $record->daysUntilExpiry();
                        if ($daysUntilExpiry <= 3) return 'danger';
                        if ($daysUntilExpiry <= 7) return 'warning';
                        return 'primary';
                    }),
                    
                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->getStateUsing(fn (Subscription $record): int => $record->daysUntilExpiry())
                    ->badge()
                    ->color(function (int $state): string {
                        if ($state <= 3) return 'danger';
                        if ($state <= 7) return 'warning';
                        return 'primary';
                    }),
            ])
            ->actions([
                Action::make('renew')
                    ->label('Quick Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('duration')
                            ->label('Renewal Duration')
                            ->options([
                                '30' => '1 Month',
                                '90' => '3 Months',
                                '180' => '6 Months',
                                '365' => '1 Year',
                            ])
                            ->default('365')
                            ->required(),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        $newExpiryDate = $record->expires_at->addDays((int) $data['duration']);
                        $record->renew($newExpiryDate);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Subscription Renewed')
                            ->body("Subscription renewed until {$newExpiryDate->format('M j, Y')}")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Renew Subscription')
                    ->modalDescription('This will extend the subscription expiry date.'),
                    
                Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Subscription $record): string => 
                        route('filament.admin.resources.subscriptions.view', $record)
                    )
                    ->openUrlInNewTab(),
            ])
            ->heading('Expiring Subscriptions')
            ->description('Subscriptions expiring within 14 days')
            ->emptyStateHeading('No expiring subscriptions')
            ->emptyStateDescription('All subscriptions are current.')
            ->defaultSort('expires_at', 'asc')
            ->defaultPaginationPageOption(10)
            ->poll('300s'); // Refresh every 5 minutes
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
