<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Subscriptions\CancelSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateSubscriptionExpiryAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Http\Requests\Superadmin\Subscriptions\UpgradeSubscriptionPlanRequest;
use App\Models\Subscription;
use App\Models\User;
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
use Illuminate\Support\Facades\Auth;

class SubscriptionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.subscriptions_resource.columns.organization'))
                    ->url(fn (Subscription $record): ?string => $record->organization === null
                        ? null
                        : OrganizationResource::getUrl('view', ['record' => $record->organization])),
                TextColumn::make('plan')
                    ->label(__('superadmin.subscriptions_resource.columns.plan'))
                    ->badge()
                    ->state(function (Subscription $record): string {
                        $plan = $record->plan;

                        return $plan instanceof SubscriptionPlan ? $plan->label() : (string) $plan;
                    }),
                TextColumn::make('status')
                    ->label(__('superadmin.subscriptions_resource.columns.status'))
                    ->badge()
                    ->state(function (Subscription $record): string {
                        $status = $record->status;

                        return $status instanceof SubscriptionStatus ? $status->label() : (string) $status;
                    }),
                TextColumn::make('starts_at')
                    ->label(__('superadmin.subscriptions_resource.columns.start_date'))
                    ->state(fn (Subscription $record): string => $record->starts_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('superadmin.subscriptions_resource.placeholders.never'))
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('superadmin.subscriptions_resource.columns.expiry_date'))
                    ->state(fn (Subscription $record): string => $record->expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? __('superadmin.subscriptions_resource.placeholders.never'))
                    ->sortable(),
                TextColumn::make('properties_used')
                    ->label(__('superadmin.subscriptions_resource.columns.properties_used'))
                    ->state(fn (Subscription $record): string => $record->propertiesUsedSummary())
                    ->color(fn (Subscription $record): string => $record->hasReachedPropertyLimit() ? 'danger' : 'gray'),
                TextColumn::make('tenants_used')
                    ->label(__('superadmin.subscriptions_resource.columns.tenants_used'))
                    ->state(fn (Subscription $record): string => $record->tenantsUsedSummary())
                    ->color(fn (Subscription $record): string => $record->hasReachedTenantLimit() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label(__('superadmin.subscriptions_resource.columns.date_created'))
                    ->state(fn (Subscription $record): string => $record->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()) ?? __('superadmin.subscriptions_resource.placeholders.never'))
                    ->sortable(),
            ])
            ->filters([
                Filter::make('organization')
                    ->label(__('superadmin.subscriptions_resource.filters.organization'))
                    ->schema([
                        TextInput::make('query')
                            ->label(__('superadmin.subscriptions_resource.filters.organization'))
                            ->placeholder(__('superadmin.subscriptions_resource.filters.organization_placeholder')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $organizationQuery = trim((string) ($data['query'] ?? ''));

                        if ($organizationQuery === '') {
                            return $query;
                        }

                        return $query->whereRelation('organization', 'name', 'like', '%'.$organizationQuery.'%');
                    }),
                SelectFilter::make('plan')
                    ->label(__('superadmin.subscriptions_resource.filters.plan'))
                    ->placeholder(__('superadmin.subscriptions_resource.filters.all_plans'))
                    ->options(SubscriptionPlan::options()),
                SelectFilter::make('status')
                    ->label(__('superadmin.subscriptions_resource.filters.status'))
                    ->placeholder(__('superadmin.subscriptions_resource.filters.all_statuses'))
                    ->options(SubscriptionStatus::options()),
                SelectFilter::make('expiring_within')
                    ->label(__('superadmin.subscriptions_resource.filters.expiring_within'))
                    ->placeholder(__('superadmin.subscriptions_resource.filters.any_time'))
                    ->options([
                        7 => __('superadmin.subscriptions_resource.filters.days', ['count' => 7]),
                        14 => __('superadmin.subscriptions_resource.filters.days', ['count' => 14]),
                        30 => __('superadmin.subscriptions_resource.filters.days', ['count' => 30]),
                        60 => __('superadmin.subscriptions_resource.filters.days', ['count' => 60]),
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
                    ->label(__('superadmin.subscriptions_resource.actions.view')),
                EditAction::make()
                    ->label(__('superadmin.subscriptions_resource.actions.edit')),
                Action::make('extendExpiry')
                    ->label(__('superadmin.subscriptions_resource.actions.extend_expiry'))
                    ->slideOver()
                    ->authorize(fn (Subscription $record): bool => self::currentUser()?->can('extend', $record) ?? false)
                    ->schema([
                        DatePicker::make('expires_at')
                            ->label(__('superadmin.subscriptions_resource.fields.new_expiry_date'))
                            ->required()
                            ->default(fn (Subscription $record): ?string => $record->expires_at?->toDateString()),
                    ])
                    ->action(function (Subscription $record, array $data, UpdateSubscriptionExpiryAction $updateSubscriptionExpiryAction): void {
                        $updateSubscriptionExpiryAction->handle($record, $data);

                        Notification::make()
                            ->title(__('superadmin.subscriptions_resource.messages.expiry_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('upgradePlan')
                    ->label(__('superadmin.subscriptions_resource.actions.upgrade_plan'))
                    ->slideOver()
                    ->authorize(fn (Subscription $record): bool => self::currentUser()?->can('upgrade', $record) ?? false)
                    ->schema([
                        TextInput::make('current_plan')
                            ->label(__('superadmin.subscriptions_resource.fields.current_plan'))
                            ->default(fn (Subscription $record): string => $record->plan->label())
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('plan')
                            ->label(__('superadmin.subscriptions_resource.fields.new_plan'))
                            ->options(SubscriptionPlan::options())
                            ->required()
                            ->default(fn (Subscription $record): string => $record->plan->value)
                            ->live()
                            ->helperText(fn (Get $get): ?string => self::selectedPlanLimitsNote($get('plan'))),
                    ])
                    ->action(function (Subscription $record, array $data, UpgradeSubscriptionPlanAction $upgradeSubscriptionPlanAction): void {
                        $validated = app(UpgradeSubscriptionPlanRequest::class)->validatePayload($data, self::currentUser());

                        $upgradeSubscriptionPlanAction->handle($record, SubscriptionPlan::from($validated['plan']));

                        Notification::make()
                            ->title(__('superadmin.subscriptions_resource.messages.plan_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('suspendSubscription')
                    ->label(__('superadmin.subscriptions_resource.actions.suspend'))
                    ->color('danger')
                    ->authorize(fn (Subscription $record): bool => self::currentUser()?->can('suspend', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(__('superadmin.subscriptions_resource.modals.suspend_description'))
                    ->action(function (Subscription $record, SuspendSubscriptionAction $suspendSubscriptionAction): void {
                        $suspendSubscriptionAction->handle($record);

                        Notification::make()
                            ->title(__('superadmin.subscriptions_resource.messages.suspended'))
                            ->success()
                            ->send();
                    }),
                Action::make('cancelSubscription')
                    ->label(__('superadmin.subscriptions_resource.actions.cancel'))
                    ->color('danger')
                    ->authorize(fn (Subscription $record): bool => self::currentUser()?->can('cancel', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(__('superadmin.subscriptions_resource.modals.cancel_description'))
                    ->action(function (Subscription $record, CancelSubscriptionAction $cancelSubscriptionAction): void {
                        $cancelSubscriptionAction->handle($record);

                        Notification::make()
                            ->title(__('superadmin.subscriptions_resource.messages.cancelled'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('superadmin.subscriptions_resource.actions.delete'))
                    ->authorize(fn (Subscription $record): bool => self::currentUser()?->can('delete', $record) ?? false),
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

        return __('superadmin.subscriptions_resource.messages.selected_plan_limits', [
            'properties' => $limits['properties'],
            'tenants' => $limits['tenants'],
        ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
