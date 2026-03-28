<?php

namespace App\Filament\Resources\Organizations\Tables;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationsSummaryAction;
use App\Filament\Actions\Superadmin\Organizations\ReinstateOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Support\Superadmin\Organizations\OrganizationListQuery;
use App\Filament\Support\Superadmin\Organizations\OrganizationMrrResolver;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        $organizationListQuery = app(OrganizationListQuery::class);
        $organizationMrrResolver = app(OrganizationMrrResolver::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.organizations.columns.name'))
                    ->url(fn (Organization $record): string => OrganizationResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('superadmin.organizations.columns.slug'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.email')
                    ->label(__('superadmin.organizations.columns.owner_email'))
                    ->placeholder(__('superadmin.organizations.empty.owner'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('superadmin.organizations.columns.status'))
                    ->badge()
                    ->state(fn (Organization $record): string => $record->status?->label() ?? '—')
                    ->color(fn (Organization $record): string => $record->status?->badgeColor() ?? 'gray')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label(__('superadmin.organizations.columns.users_count'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('mrr_display')
                    ->label(__('superadmin.organizations.columns.mrr'))
                    ->state(fn (Organization $record): string => $organizationMrrResolver->displayFor($record))
                    ->alignCenter(),
                TextColumn::make('currentSubscription.plan')
                    ->label(__('superadmin.organizations.overview.fields.current_plan'))
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionPlan $state): string => $state?->label() ?? __('superadmin.organizations.overview.placeholders.no_plan'))
                    ->color('primary'),
                TextColumn::make('currentSubscription.status')
                    ->label(__('superadmin.organizations.overview.fields.subscription_status'))
                    ->badge()
                    ->formatStateUsing(fn (?SubscriptionStatus $state): string => $state?->label() ?? __('superadmin.organizations.overview.placeholders.no_subscription'))
                    ->color(fn (?SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::ACTIVE => 'success',
                        SubscriptionStatus::EXPIRED => 'danger',
                        SubscriptionStatus::SUSPENDED => 'warning',
                        SubscriptionStatus::CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('trial_or_grace_ends')
                    ->label(__('superadmin.organizations.columns.trial_or_grace_ends'))
                    ->state(fn (Organization $record): string => self::trialOrGraceEnds($record))
                    ->alignCenter(),
                TextColumn::make('buildings_count')
                    ->label(__('superadmin.organizations.relations.buildings.title'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('properties_count')
                    ->label(__('superadmin.organizations.overview.usage_labels.properties'))
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('tenants_count')
                    ->label(__('superadmin.organizations.overview.usage_labels.tenants'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meters_count')
                    ->label(__('superadmin.organizations.overview.usage_labels.meters'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoices_count')
                    ->label(__('superadmin.organizations.overview.usage_labels.invoices'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.columns.created_at'))
                    ->state(fn (Organization $record): string => $record->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('superadmin.organizations.columns.status'))
                    ->placeholder(__('superadmin.organizations.filters.all_statuses'))
                    ->multiple()
                    ->options(OrganizationStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => $organizationListQuery->filterByStatuses(
                        $query,
                        $data['values'] ?? [],
                    )),
                SelectFilter::make('plan')
                    ->label(__('superadmin.organizations.overview.fields.current_plan'))
                    ->placeholder(__('superadmin.organizations.filters.all_plans'))
                    ->multiple()
                    ->options(SubscriptionPlan::options())
                    ->query(fn (Builder $query, array $data): Builder => $organizationListQuery->filterByPlans(
                        $query,
                        $data['values'] ?? [],
                    )),
                Filter::make('created_between')
                    ->label(__('superadmin.organizations.columns.created_at'))
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('superadmin.organizations.filters.created_from')),
                        DatePicker::make('created_to')
                            ->label(__('superadmin.organizations.filters.created_to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $organizationListQuery->filterByCreatedBetween(
                        $query,
                        $data['created_from'] ?? null,
                        $data['created_to'] ?? null,
                    )),
                Filter::make('trial_expiry_range')
                    ->label(__('superadmin.organizations.filters.trial_expiry'))
                    ->schema([
                        DatePicker::make('trial_expires_from')
                            ->label(__('superadmin.organizations.filters.trial_expires_from')),
                        DatePicker::make('trial_expires_to')
                            ->label(__('superadmin.organizations.filters.trial_expires_to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $organizationListQuery->filterByTrialExpiryRange(
                        $query,
                        $data['trial_expires_from'] ?? null,
                        $data['trial_expires_to'] ?? null,
                    )),
                TernaryFilter::make('has_overdue_invoices')
                    ->label(__('superadmin.organizations.filters.has_overdue_invoices'))
                    ->queries(
                        true: fn (Builder $query): Builder => $organizationListQuery->filterByOverdueInvoicePresence($query, true),
                        false: fn (Builder $query): Builder => $organizationListQuery->filterByOverdueInvoicePresence($query, false),
                        blank: fn (Builder $query): Builder => $organizationListQuery->filterByOverdueInvoicePresence($query, null),
                    ),
                TernaryFilter::make('has_security_violations')
                    ->label(__('superadmin.organizations.filters.has_security_violations'))
                    ->queries(
                        true: fn (Builder $query): Builder => $organizationListQuery->filterBySecurityViolationPresence($query, true),
                        false: fn (Builder $query): Builder => $organizationListQuery->filterBySecurityViolationPresence($query, false),
                        blank: fn (Builder $query): Builder => $organizationListQuery->filterBySecurityViolationPresence($query, null),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('superadmin.organizations.actions.view')),
                EditAction::make()
                    ->label(__('superadmin.organizations.actions.edit')),
                ActionGroup::make([
                    Action::make('suspendOrganization')
                        ->label(__('superadmin.organizations.actions.suspend'))
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->color('danger')
                        ->visible(fn (Organization $record): bool => $record->status->permitsAccess())
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('suspend', $record) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(fn (Organization $record): string => __('superadmin.organizations.modals.suspend_now', ['name' => $record->name]))
                        ->action(function (Organization $record, SuspendOrganizationAction $suspendOrganizationAction): void {
                            $suspendOrganizationAction->handle($record);

                            Notification::make()
                                ->title(__('superadmin.organizations.notifications.suspended'))
                                ->success()
                                ->send();
                        }),
                    Action::make('reinstateOrganization')
                        ->label(__('superadmin.organizations.actions.reinstate'))
                        ->icon(Heroicon::OutlinedPlayCircle)
                        ->color('success')
                        ->visible(fn (Organization $record): bool => $record->status === OrganizationStatus::SUSPENDED)
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('reinstate', $record) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(fn (Organization $record): string => __('superadmin.organizations.modals.reinstate', ['name' => $record->name]))
                        ->action(function (Organization $record, ReinstateOrganizationAction $reinstateOrganizationAction): void {
                            $reinstateOrganizationAction->handle($record);

                            Notification::make()
                                ->title(__('superadmin.organizations.notifications.reinstated'))
                                ->success()
                                ->send();
                        }),
                    Action::make('sendNotification')
                        ->label(__('superadmin.organizations.actions.send_notification'))
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->slideOver()
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('update', $record) ?? false)
                        ->schema([
                            TextInput::make('title')
                                ->label(__('superadmin.organizations.form.fields.notification_title'))
                                ->required()
                                ->maxLength(255),
                            Textarea::make('body')
                                ->label(__('superadmin.organizations.form.fields.message_body'))
                                ->required()
                                ->rows(5),
                            Select::make('severity')
                                ->label(__('superadmin.organizations.form.fields.severity'))
                                ->options([
                                    'information' => __('superadmin.organizations.form.severity_options.information'),
                                    'warning' => __('superadmin.organizations.form.severity_options.warning'),
                                    'critical' => __('superadmin.organizations.form.severity_options.critical'),
                                ])
                                ->default('information')
                                ->required(),
                        ])
                        ->action(function (Organization $record, array $data, SendOrganizationNotificationAction $sendOrganizationNotificationAction): void {
                            $sendOrganizationNotificationAction->handle(
                                $record,
                                $data['title'],
                                $data['body'],
                                $data['severity'],
                            );

                            Notification::make()
                                ->title(__('superadmin.organizations.notifications.sent'))
                                ->success()
                                ->send();
                        }),
                    Action::make('impersonateAdmin')
                        ->label(__('superadmin.organizations.actions.impersonate_admin'))
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->visible(fn (Organization $record): bool => $record->status->permitsAccess())
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('impersonate', $record) ?? false)
                        ->requiresConfirmation()
                        ->modalDescription(__('superadmin.organizations.modals.impersonate'))
                        ->action(function (Organization $record, StartOrganizationImpersonationAction $startOrganizationImpersonationAction): void {
                            $impersonator = self::currentUser();
                            $admin = self::resolvePrimaryAdmin($record);

                            abort_if(! $impersonator instanceof User, 403);
                            abort_if(! $admin instanceof User, 404, __('superadmin.organizations.messages.no_primary_admin'));

                            $startOrganizationImpersonationAction->handle($impersonator, $admin);
                        }),
                    Action::make('exportData')
                        ->label(__('superadmin.organizations.actions.export_data'))
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->authorize(fn (Organization $record): bool => self::currentUser()?->can('view', $record) ?? false)
                        ->action(function (Organization $record, ExportOrganizationDataAction $exportOrganizationDataAction) {
                            $path = $exportOrganizationDataAction->handle($record);

                            return response()
                                ->download($path, "{$record->slug}-organization-export.zip")
                                ->deleteFileAfterSend(true);
                        }),
                    DeleteAction::make('deleteOrganization')
                        ->label(__('superadmin.organizations.actions.delete')),
                ])
                    ->label(__('superadmin.organizations.actions.more'))
                    ->icon(Heroicon::OutlinedEllipsisHorizontal),
            ])
            ->toolbarActions([
                BulkAction::make('suspendSelected')
                    ->label(__('superadmin.organizations.actions.suspend_selected'))
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('danger')
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(__('superadmin.organizations.modals.suspend_selected'))
                    ->action(function (Collection $records, SuspendOrganizationAction $suspendOrganizationAction): void {
                        $records
                            ->filter(fn (Organization $record): bool => $record->status->permitsAccess())
                            ->each(fn (Organization $record): Organization => $suspendOrganizationAction->handle($record));

                        Notification::make()
                            ->title(__('superadmin.organizations.notifications.suspended'))
                            ->success()
                            ->send();
                    }),
                BulkAction::make('reinstateSelected')
                    ->label(__('superadmin.organizations.actions.reinstate_selected'))
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->requiresConfirmation()
                    ->modalDescription(__('superadmin.organizations.modals.reinstate_selected'))
                    ->action(function (Collection $records, ReinstateOrganizationAction $reinstateOrganizationAction): void {
                        $records
                            ->filter(fn (Organization $record): bool => $record->status === OrganizationStatus::SUSPENDED)
                            ->each(fn (Organization $record): Organization => $reinstateOrganizationAction->handle($record));

                        Notification::make()
                            ->title(__('superadmin.organizations.notifications.reinstated'))
                            ->success()
                            ->send();
                    }),
                DeleteBulkAction::make('deleteSelected')
                    ->label(__('superadmin.organizations.actions.delete_selected')),
                BulkAction::make('exportSelected')
                    ->label(__('superadmin.organizations.actions.export_selected'))
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->action(function (Collection $records, ExportOrganizationsSummaryAction $exportOrganizationsSummaryAction, Table $table) {
                        $path = $exportOrganizationsSummaryAction->handle(
                            $records,
                            array_keys($table->getVisibleColumns()),
                        );

                        return response()
                            ->download($path, 'organizations-export.csv')
                            ->deleteFileAfterSend(true);
                    }),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->searchPlaceholder(__('superadmin.organizations.search_placeholder'))
            ->recordClasses(fn (Organization $record): array => self::recordClassesFor($record))
            ->defaultPaginationPageOption(20)
            ->paginationPageOptions([20])
            ->defaultSort('created_at', 'desc');
    }

    private static function trialOrGraceEnds(Organization $organization): string
    {
        $subscription = $organization->currentSubscription;

        if (! $subscription?->expires_at) {
            return '—';
        }

        if (! $subscription->is_trial && $subscription->status !== SubscriptionStatus::TRIALING) {
            return '—';
        }

        return $subscription->expires_at->locale(app()->getLocale())->isoFormat('ll');
    }

    /**
     * @return array<int, string>
     */
    private static function recordClassesFor(Organization $organization): array
    {
        if (in_array($organization->status, [OrganizationStatus::SUSPENDED, OrganizationStatus::CANCELLED], true)) {
            return ['bg-danger-50/80'];
        }

        $subscription = $organization->currentSubscription;

        if ($subscription?->is_trial || $subscription?->status === SubscriptionStatus::TRIALING) {
            return ['bg-info-50/80'];
        }

        if ($organization->status === OrganizationStatus::PENDING) {
            return ['bg-warning-50/80'];
        }

        return [];
    }

    private static function resolvePrimaryAdmin(Organization $organization): ?User
    {
        $owner = $organization->owner;

        if ($owner instanceof User && $owner->role === UserRole::ADMIN) {
            return $owner;
        }

        return $organization->users()
            ->select([
                'id',
                'organization_id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'last_login_at',
                'created_at',
                'updated_at',
                'password',
                'remember_token',
            ])
            ->where('role', UserRole::ADMIN)
            ->orderedByName()
            ->first();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
