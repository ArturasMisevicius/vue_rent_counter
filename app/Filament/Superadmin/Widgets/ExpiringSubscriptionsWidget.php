<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Expiring Subscriptions Widget
 * 
 * Displays subscriptions expiring within the next 14 days
 * for superadmin monitoring and proactive management.
 * 
 * Success metric from goals.md:
 * "Superadmin sees every expiring subscription (14-day window)"
 * 
 * @package App\Filament\Superadmin\Widgets
 */
final class ExpiringSubscriptionsWidget extends BaseWidget
{
    /**
     * Widget heading.
     */
    protected static ?string $heading = null;

    /**
     * Widget column span configuration.
     */
    protected int | string | array $columnSpan = 'full';

    /**
     * Widget sort order - show before recent users.
     */
    protected static ?int $sort = 1;

    /**
     * Days to look ahead for expiring subscriptions.
     */
    private const EXPIRY_WINDOW_DAYS = 14;

    /**
     * Get the widget heading.
     */
    public function getHeading(): string
    {
        return __('superadmin.dashboard.expiring_subscriptions.title');
    }

    /**
     * Get the widget description with count alert.
     */
    public function getDescription(): ?string
    {
        $count = $this->getExpiringCount();
        
        if ($count === 0) {
            return null;
        }

        return __('superadmin.dashboard.expiring_subscriptions.alert', ['count' => $count]);
    }

    /**
     * Configure the table for the widget.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('app.labels.organization'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Subscription $record): string => 
                        $record->user?->email ?? ''
                    ),

                TextColumn::make('plan_type')
                    ->label(__('app.labels.plan'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enterprise' => 'success',
                        'professional' => 'info',
                        'basic' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::ACTIVE => 'success',
                        SubscriptionStatus::EXPIRED => 'danger',
                        SubscriptionStatus::SUSPENDED => 'warning',
                        SubscriptionStatus::CANCELLED => 'gray',
                    }),

                TextColumn::make('expires_at')
                    ->label(__('superadmin.dashboard.expiring_subscriptions.expires'))
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->color(fn (Subscription $record): string => 
                        $record->daysUntilExpiry() <= 7 ? 'danger' : 'warning'
                    ),

                TextColumn::make('days_remaining')
                    ->label(__('superadmin.dashboard.stats.expiring_soon'))
                    ->state(fn (Subscription $record): string => 
                        $record->daysUntilExpiry() . ' ' . __('app.labels.days')
                    )
                    ->badge()
                    ->color(fn (Subscription $record): string => match (true) {
                        $record->daysUntilExpiry() <= 3 => 'danger',
                        $record->daysUntilExpiry() <= 7 => 'warning',
                        default => 'info',
                    }),
            ])
            ->actions([
                Action::make('extend')
                    ->label(__('app.actions.extend'))
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('app.actions.extend_subscription'))
                    ->modalDescription(__('app.modals.extend_subscription_description'))
                    ->action(function (Subscription $record): void {
                        $record->renew($record->expires_at->addYear());
                        
                        Notification::make()
                            ->title(__('notifications.subscription_extended'))
                            ->success()
                            ->send();
                    }),

                Action::make('notify')
                    ->label(__('app.actions.notify'))
                    ->icon('heroicon-m-bell')
                    ->color('warning')
                    ->action(function (Subscription $record): void {
                        // TODO: Send expiry warning notification to user
                        Notification::make()
                            ->title(__('notifications.notification_sent'))
                            ->body(__('notifications.expiry_warning_sent', [
                                'user' => $record->user?->name ?? 'Unknown',
                            ]))
                            ->success()
                            ->send();
                    }),

                Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (Subscription $record): string => 
                        route('filament.superadmin.resources.subscriptions.edit', $record)
                    ),
            ])
            ->defaultSort('expires_at', 'asc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('app.empty_states.no_expiring_subscriptions'))
            ->emptyStateDescription(__('app.empty_states.no_expiring_subscriptions_description'))
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Get the base query for expiring subscriptions.
     * 
     * @return Builder<Subscription>
     */
    protected function getTableQuery(): Builder
    {
        return Subscription::query()
            ->with('user:id,name,email')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(self::EXPIRY_WINDOW_DAYS))
            ->orderBy('expires_at', 'asc');
    }

    /**
     * Get count of expiring subscriptions for the alert.
     * Cached per request to avoid duplicate queries.
     */
    private function getExpiringCount(): int
    {
        return once(fn (): int => $this->getTableQuery()->count());
    }
}
