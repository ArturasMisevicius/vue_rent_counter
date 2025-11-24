<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiringSubscriptionsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->where('status', SubscriptionStatus::ACTIVE->value)
                    ->where('expires_at', '>=', now())
                    ->where('expires_at', '<=', now()->addDays(14))
                    ->with('user')
                    ->orderBy('expires_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan_type')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                    ->color(fn ($state): string => match ($state) {
                        SubscriptionPlanType::BASIC->value => 'gray',
                        SubscriptionPlanType::PROFESSIONAL->value => 'info',
                        SubscriptionPlanType::ENTERPRISE->value => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->daysUntilExpiry() <= 7 ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->state(fn (Subscription $record) => $record->daysUntilExpiry())
                    ->badge()
                    ->color(fn ($state) => $state <= 7 ? 'danger' : 'warning'),
            ])
            ->actions([
                Tables\Actions\Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\DateTimePicker::make('new_expires_at')
                            ->label('New Expiration Date')
                            ->required()
                            ->after('today')
                            ->default(now()->addYear()),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update([
                            'expires_at' => $data['new_expires_at'],
                            'status' => SubscriptionStatus::ACTIVE->value,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Subscription renewed successfully'),

                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Subscription $record): string => route('filament.admin.resources.subscriptions.view', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->heading('Subscriptions Expiring Soon (14 Days)')
            ->description('Active subscriptions that will expire within the next 14 days')
            ->emptyStateHeading('No expiring subscriptions')
            ->emptyStateDescription('All subscriptions are valid for more than 14 days')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
