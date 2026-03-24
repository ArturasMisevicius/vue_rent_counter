<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Subscriptions\CancelSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateSubscriptionExpiryAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Http\Requests\Superadmin\Subscriptions\UpgradeSubscriptionPlanRequest;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->url(fn (Subscription $record): ?string => $record->organization === null
                        ? null
                        : OrganizationResource::getUrl('view', ['record' => $record->organization])),
                TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->state(fn (Subscription $record): string => $record->plan->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Subscription $record): string => $record->status->label()),
                TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->state(fn (Subscription $record): string => $record->starts_at?->format('Y-m-d') ?? 'Never')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expiry Date')
                    ->state(fn (Subscription $record): string => $record->expires_at?->format('Y-m-d') ?? 'Never')
                    ->sortable(),
                TextColumn::make('properties_used')
                    ->label('Properties Used')
                    ->state(fn (Subscription $record): string => $record->propertiesUsedSummary())
                    ->color(fn (Subscription $record): string => $record->hasReachedPropertyLimit() ? 'danger' : 'gray'),
                TextColumn::make('tenants_used')
                    ->label('Tenants Used')
                    ->state(fn (Subscription $record): string => $record->tenantsUsedSummary())
                    ->color(fn (Subscription $record): string => $record->hasReachedTenantLimit() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->state(fn (Subscription $record): string => $record->created_at?->format('Y-m-d H:i') ?? 'Never')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('organization')
                    ->label('Organization')
                    ->schema([
                        TextInput::make('query')
                            ->label('Organization')
                            ->placeholder('Search organizations'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $organizationQuery = trim((string) ($data['query'] ?? ''));

                        if ($organizationQuery === '') {
                            return $query;
                        }

                        return $query->whereRelation('organization', 'name', 'like', '%'.$organizationQuery.'%');
                    }),
                SelectFilter::make('plan')
                    ->label('Plan')
                    ->placeholder('All Plans')
                    ->options(SubscriptionPlan::options()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All Statuses')
                    ->options(SubscriptionStatus::options()),
                SelectFilter::make('expiring_within')
                    ->label('Expiring Within')
                    ->placeholder('Any Time')
                    ->options([
                        7 => '7 Days',
                        14 => '14 Days',
                        30 => '30 Days',
                        60 => '60 Days',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $days = $data['value'] ?? null;

                        if (blank($days)) {
                            return $query;
                        }

                        return $query->expiringWithin((int) $days);
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View'),
                EditAction::make()
                    ->label('Edit'),
                Action::make('extendExpiry')
                    ->label('Extend Expiry')
                    ->slideOver()
                    ->authorize(fn (Subscription $record): bool => auth()->user()?->can('extend', $record) ?? false)
                    ->schema([
                        DatePicker::make('expires_at')
                            ->label('New Expiry Date')
                            ->required()
                            ->default(fn (Subscription $record): ?string => $record->expires_at?->toDateString()),
                    ])
                    ->action(function (Subscription $record, array $data, UpdateSubscriptionExpiryAction $updateSubscriptionExpiryAction): void {
                        $updateSubscriptionExpiryAction->handle($record, $data);

                        Notification::make()
                            ->title('Subscription expiry updated')
                            ->success()
                            ->send();
                    }),
                Action::make('upgradePlan')
                    ->label('Upgrade Plan')
                    ->slideOver()
                    ->authorize(fn (Subscription $record): bool => auth()->user()?->can('upgrade', $record) ?? false)
                    ->schema([
                        TextInput::make('current_plan')
                            ->label('Current Plan')
                            ->default(fn (Subscription $record): string => $record->plan->label())
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('plan')
                            ->label('New Plan')
                            ->options(SubscriptionPlan::options())
                            ->required()
                            ->default(fn (Subscription $record): string => $record->plan->value)
                            ->live()
                            ->helperText(fn (Get $get): ?string => self::selectedPlanLimitsNote($get('plan'))),
                    ])
                    ->action(function (Subscription $record, array $data, UpgradeSubscriptionPlanAction $upgradeSubscriptionPlanAction): void {
                        $validated = app(UpgradeSubscriptionPlanRequest::class)->validatePayload($data, auth()->user());

                        $upgradeSubscriptionPlanAction->handle($record, SubscriptionPlan::from($validated['plan']));

                        Notification::make()
                            ->title('Subscription plan updated')
                            ->success()
                            ->send();
                    }),
                Action::make('suspendSubscription')
                    ->label('Suspend')
                    ->color('danger')
                    ->authorize(fn (Subscription $record): bool => auth()->user()?->can('suspend', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('Suspending this subscription immediately restricts access to subscription-backed billing features.')
                    ->action(function (Subscription $record, SuspendSubscriptionAction $suspendSubscriptionAction): void {
                        $suspendSubscriptionAction->handle($record);

                        Notification::make()
                            ->title('Subscription suspended')
                            ->success()
                            ->send();
                    }),
                Action::make('cancelSubscription')
                    ->label('Cancel')
                    ->color('danger')
                    ->authorize(fn (Subscription $record): bool => auth()->user()?->can('cancel', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription('Cancelling this subscription ends the subscription lifecycle and prevents future renewals for the organization.')
                    ->action(function (Subscription $record, CancelSubscriptionAction $cancelSubscriptionAction): void {
                        $cancelSubscriptionAction->handle($record);

                        Notification::make()
                            ->title('Subscription cancelled')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label('Delete')
                    ->authorize(fn (Subscription $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc');
    }

    private static function selectedPlanLimitsNote(?string $plan): ?string
    {
        if (blank($plan)) {
            return null;
        }

        $selectedPlan = SubscriptionPlan::from($plan);
        $limits = $selectedPlan->limits();

        return "New limits: {$limits['properties']} properties and {$limits['tenants']} tenants.";
    }
}
