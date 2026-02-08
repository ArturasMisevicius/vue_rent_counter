<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;


use BackedEnum;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('renew')
                ->label(__('subscriptions.actions.renew'))
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label(__('subscriptions.labels.new_expiration_date'))
                        ->required()
                        ->after('today')
                        ->default(now()->addYear()),
                ])
                ->action(function (Subscription $record, array $data): void {
                    $newExpiry = Carbon::parse($data['expires_at']);

                    app(SubscriptionService::class)->renewSubscription($record, $newExpiry);
                })
                ->visible(fn (Subscription $record): bool => in_array($record->status, [
                    SubscriptionStatus::ACTIVE,
                    SubscriptionStatus::EXPIRED,
                ], true))
                ->requiresConfirmation()
                ->successNotificationTitle(__('subscriptions.notifications.renewed')),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('subscriptions.labels.organization'))
                    ->schema([
                        Infolists\Components\TextEntry::make('user.organization_name')
                            ->label(__('subscriptions.labels.organization')),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label(__('subscriptions.labels.email')),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label(__('subscriptions.labels.contact_name')),
                    ])->columns(3),

                Section::make(__('subscriptions.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('plan_type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                            ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                                SubscriptionPlanType::BASIC->value => 'gray',
                                SubscriptionPlanType::PROFESSIONAL->value => 'info',
                                SubscriptionPlanType::ENTERPRISE->value => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionStatus::class))
                            ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                                SubscriptionStatus::ACTIVE->value => 'success',
                                SubscriptionStatus::EXPIRED->value => 'danger',
                                SubscriptionStatus::SUSPENDED->value => 'warning',
                                SubscriptionStatus::CANCELLED->value => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('starts_at')
                            ->label(__('subscriptions.labels.starts_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('expires_at')
                            ->label(__('subscriptions.labels.expires_at'))
                            ->dateTime()
                            ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),
                        Infolists\Components\TextEntry::make('days_until_expiry')
                            ->label(__('subscriptions.labels.days_until_expiry'))
                            ->state(fn ($record) => $record->daysUntilExpiry())
                            ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 14 ? 'warning' : 'success')),
                    ])->columns(3),

                Section::make(__('subscriptions.sections.limits'))
                    ->schema([
                        Infolists\Components\TextEntry::make('max_properties')
                            ->label(__('subscriptions.labels.max_properties')),
                        Infolists\Components\TextEntry::make('max_tenants')
                            ->label(__('subscriptions.labels.max_tenants')),
                    ])->columns(2),

                Section::make(__('subscriptions.sections.usage'))
                    ->schema([
                        Infolists\Components\TextEntry::make('properties_used')
                            ->label(__('subscriptions.labels.properties_used'))
                            ->state(fn ($record) => $record->user->properties()->withoutGlobalScopes()->count()),
                        Infolists\Components\TextEntry::make('properties_remaining')
                            ->label(__('subscriptions.labels.properties_remaining'))
                            ->state(fn ($record) => max(0, $record->max_properties - $record->user->properties()->withoutGlobalScopes()->count())),
                        Infolists\Components\TextEntry::make('tenants_used')
                            ->label(__('subscriptions.labels.tenants_used'))
                            ->state(fn ($record) => $record->user->childUsers()->count()),
                        Infolists\Components\TextEntry::make('tenants_remaining')
                            ->label(__('subscriptions.labels.tenants_remaining'))
                            ->state(fn ($record) => max(0, $record->max_tenants - $record->user->childUsers()->count())),
                    ])->columns(4),

                Section::make(__('subscriptions.sections.timestamps'))
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('subscriptions.labels.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('subscriptions.labels.updated_at') ?? 'Updated At')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }
}
