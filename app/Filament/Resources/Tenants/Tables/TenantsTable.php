<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Enums\InvitationStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MoveOutProcessStatus;
use App\Enums\PortalAccessStatus;
use App\Enums\RentalContractStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\DisableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\EnableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\ResendTenantInvitation;
use App\Filament\Actions\Admin\Tenants\RevokeTenantInvitation;
use App\Filament\Actions\Admin\Tenants\SendTenantInvitation;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAttentionQuery($query))
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.tenants.columns.full_name'))
                    ->url(fn (User $record): string => TenantResource::getUrl('view', ['record' => $record]))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.tenants.columns.email'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('locale')
                    ->label(__('admin.tenants.fields.preferred_language'))
                    ->state(fn (User $record): string => (string) (config('tenanto.locales')[$record->locale] ?? $record->locale))
                    ->toggleable(),
                TextColumn::make('currentPropertyAssignment.property.name')
                    ->label(__('admin.tenants.columns.property'))
                    ->state(fn (User $record): string => $record->currentProperty?->displayName() ?? __('admin.tenants.empty.unassigned'))
                    ->url(fn (User $record): ?string => $record->currentProperty !== null
                        ? PropertyResource::getUrl('view', ['record' => $record->currentProperty])
                        : null)
                    ->sortable(),
                TextColumn::make('leaseAgreement.original_filename')
                    ->label(__('admin.tenants.columns.lease_agreement'))
                    ->state(fn (User $record): string => $record->leaseAgreement?->original_filename ?? __('admin.tenants.empty.no_lease_agreement'))
                    ->icon(fn (User $record) => TenantLeaseAgreement::iconForAttachment($record->leaseAgreement))
                    ->color(fn (User $record): string => $record->leaseAgreement === null ? 'gray' : 'success')
                    ->url(fn (User $record): ?string => $record->leaseAgreement !== null
                        ? route('tenant.attachments.show', ['attachment' => $record->leaseAgreement])
                        : null)
                    ->openUrlInNewTab()
                    ->toggleable(),
                TextColumn::make('unit_area')
                    ->label(__('admin.tenants.columns.unit_area'))
                    ->state(fn (User $record): string => $record->currentUnitAreaDisplay()),
                TextColumn::make('phone')
                    ->label(__('admin.tenants.columns.phone'))
                    ->default('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('portal_access_status')
                    ->label(__('admin.tenants.columns.portal_access_status'))
                    ->state(fn (User $record): string => $record->portalAccessStatus()->getLabel())
                    ->badge()
                    ->color(fn (User $record): string => self::portalStatusColor($record->portalAccessStatus()))
                    ->toggleable(),
                TextColumn::make('invitation_status')
                    ->label(__('admin.tenants.columns.invitation_status'))
                    ->state(fn (User $record): string => self::invitationStatusLabel($record->latestTenantInvitationRecord()))
                    ->badge()
                    ->color(fn (User $record): string => self::invitationStatusColor($record->latestTenantInvitationRecord()?->invitationStatus()))
                    ->toggleable(),
                TextColumn::make('latestTenantInvitation.sent_at')
                    ->label(__('admin.tenants.columns.invitation_sent_at'))
                    ->state(fn (User $record): string => self::dateTime($record->latestTenantInvitationRecord()?->sent_at))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('latestTenantInvitation.accepted_at')
                    ->label(__('admin.tenants.columns.accepted_at'))
                    ->state(fn (User $record): string => self::dateTime($record->latestTenantInvitationRecord()?->accepted_at))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_login_at')
                    ->label(__('admin.tenants.fields.last_login'))
                    ->state(fn (User $record): string => $record->last_login_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()) ?? __('admin.tenants.empty.never'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.tenants.columns.date_added'))
                    ->state(fn (User $record): string => $record->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->forOrganization((int) $data['value'])
                        : $query),
                SelectFilter::make('property_id')
                    ->label(__('admin.tenants.fields.property'))
                    ->placeholder(__('admin.tenants.filters.all_properties'))
                    ->options(fn (): array => self::propertyFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $propertyId = $data['value'] ?? null;

                        if (blank($propertyId)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'currentPropertyAssignment',
                            fn (Builder $assignmentQuery): Builder => $assignmentQuery->where('property_id', $propertyId),
                        );
                    }),
                SelectFilter::make('locale')
                    ->label(__('admin.tenants.fields.preferred_language'))
                    ->placeholder(__('admin.tenants.filters.all_languages'))
                    ->options(config('tenanto.locales', []))
                    ->query(function (Builder $query, array $data): Builder {
                        $locale = $data['value'] ?? null;

                        if (blank($locale)) {
                            return $query;
                        }

                        return $query->where('locale', $locale);
                    }),
                SelectFilter::make('status')
                    ->label(__('admin.tenants.columns.status'))
                    ->placeholder(__('admin.tenants.filters.all_statuses'))
                    ->options([
                        UserStatus::ACTIVE->value => UserStatus::ACTIVE->getLabel(),
                        UserStatus::INACTIVE->value => UserStatus::INACTIVE->getLabel(),
                        UserStatus::SUSPENDED->value => UserStatus::SUSPENDED->getLabel(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (blank($status)) {
                            return $query;
                        }

                        return $query->where('status', $status);
                    }),
            ])
            ->emptyStateHeading(__('admin.tenants.empty_state.heading'))
            ->emptyStateDescription(__('admin.tenants.empty_state.description'))
            ->emptyStateActions(
                TenantResource::shouldShowBlockedCreateAction('tenants')
                    ? [
                        TenantResource::makeSubscriptionInfoAction(
                            name: 'create',
                            resource: 'tenants',
                            label: __('admin.tenants.actions.create_tenant'),
                        ),
                    ]
                    : (
                        TenantResource::canCreate()
                            ? [
                                Action::make('createTenant')
                                    ->label(__('admin.tenants.actions.create_tenant'))
                                    ->url(TenantResource::getUrl('create'))
                                    ->icon('heroicon-m-plus')
                                    ->button(),
                            ]
                            : []
                    ),
            )
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                ...(
                    TenantResource::shouldInterceptGraceEditAction()
                        ? [
                            TenantResource::makeSubscriptionInfoAction(
                                name: 'edit',
                                resource: 'tenants',
                                label: __('admin.tenants.actions.edit_tenant'),
                            ),
                        ]
                        : (
                            TenantResource::hidesSubscriptionWriteActions()
                                ? []
                                : [
                                    EditAction::make()
                                        ->label(__('admin.tenants.actions.edit_tenant')),
                                ]
                        )
                ),
                ...(
                    TenantResource::canMutateSubscriptionScopedRecords()
                        ? [
                            Action::make('sendInvitation')
                                ->label(__('admin.tenants.actions.send_invitation'))
                                ->icon('heroicon-m-envelope')
                                ->visible(fn (User $record): bool => self::canSendInvitation($record))
                                ->action(function (User $record, SendTenantInvitation $sendTenantInvitation): void {
                                    $actor = self::currentUser();

                                    abort_if(! $actor instanceof User, 403);

                                    $sendTenantInvitation->handle($actor, $record);

                                    Notification::make()
                                        ->success()
                                        ->title(__('admin.tenants.messages.invitation_sent', ['email' => $record->email]))
                                        ->send();
                                }),
                            Action::make('resendInvitation')
                                ->label(__('admin.tenants.actions.resend_invitation'))
                                ->icon('heroicon-m-arrow-path')
                                ->visible(fn (User $record): bool => self::canResendInvitation($record))
                                ->action(function (User $record, ResendTenantInvitation $resendTenantInvitation): void {
                                    $actor = self::currentUser();

                                    abort_if(! $actor instanceof User, 403);

                                    $resendTenantInvitation->handle($actor, $record);

                                    Notification::make()
                                        ->success()
                                        ->title(__('admin.tenants.messages.invitation_resent'))
                                        ->send();
                                }),
                            Action::make('copyInvitationLink')
                                ->label(__('admin.tenants.actions.copy_invitation_link'))
                                ->icon('heroicon-m-clipboard-document')
                                ->modalHeading(__('admin.tenants.actions.copy_invitation_link'))
                                ->modalDescription(__('admin.tenants.messages.copy_invitation_link_description'))
                                ->schema([
                                    TextInput::make('invitation_link')
                                        ->label(__('admin.tenants.fields.invitation_link'))
                                        ->readOnly()
                                        ->copyable(copyMessage: __('admin.tenants.messages.invitation_link_copied')),
                                ])
                                ->mountUsing(function (Schema $form, User $record): void {
                                    $actor = self::currentUser();

                                    abort_if(! $actor instanceof User, 403);

                                    $invitation = app(SendTenantInvitation::class)->handle($actor, $record, sendEmail: false);

                                    $form->fill([
                                        'invitation_link' => route('invitation.show', $invitation->acceptanceToken),
                                    ]);
                                })
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel(__('admin.actions.close'))
                                ->visible(fn (User $record): bool => self::canCopyInvitationLink($record)),
                            Action::make('revokeInvitation')
                                ->label(__('admin.tenants.actions.revoke_invitation'))
                                ->icon('heroicon-m-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->visible(fn (User $record): bool => self::canRevokeInvitation($record))
                                ->action(function (User $record, RevokeTenantInvitation $revokeTenantInvitation): void {
                                    $actor = self::currentUser();
                                    $invitation = $record->latestTenantInvitationRecord();

                                    abort_if(! $actor instanceof User || ! $invitation instanceof OrganizationInvitation, 403);

                                    $revokeTenantInvitation->handle($actor, $invitation);

                                    Notification::make()
                                        ->success()
                                        ->title(__('admin.tenants.messages.invitation_revoked'))
                                        ->send();
                                }),
                            Action::make('enablePortalAccess')
                                ->label(__('admin.tenants.actions.enable_portal_access'))
                                ->icon('heroicon-m-lock-open')
                                ->color('success')
                                ->visible(fn (User $record): bool => self::canEnablePortalAccess($record))
                                ->action(function (User $record, EnableTenantPortalAccess $enableTenantPortalAccess): void {
                                    $actor = self::currentUser();

                                    abort_if(! $actor instanceof User, 403);

                                    $enableTenantPortalAccess->handle($actor, $record);

                                    Notification::make()
                                        ->success()
                                        ->title(__('admin.tenants.messages.portal_access_enabled'))
                                        ->send();
                                }),
                            Action::make('disablePortalAccess')
                                ->label(__('admin.tenants.actions.disable_portal_access'))
                                ->icon('heroicon-m-lock-closed')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->visible(fn (User $record): bool => self::canDisablePortalAccess($record))
                                ->action(function (User $record, DisableTenantPortalAccess $disableTenantPortalAccess): void {
                                    $actor = self::currentUser();

                                    abort_if(! $actor instanceof User, 403);

                                    $disableTenantPortalAccess->handle($actor, $record);

                                    Notification::make()
                                        ->success()
                                        ->title(__('admin.tenants.messages.portal_access_disabled'))
                                        ->send();
                                }),
                        ]
                        : []
                ),
                ...(
                    TenantResource::canMutateSubscriptionScopedRecords()
                        ? [
                            Action::make('toggleStatus')
                                ->label(fn (User $record): string => $record->status === UserStatus::ACTIVE
                                    ? __('admin.tenants.actions.deactivate')
                                    : __('admin.tenants.actions.reactivate'))
                                ->color(fn (User $record): string => $record->status === UserStatus::ACTIVE ? 'warning' : 'success')
                                ->requiresConfirmation()
                                ->action(function (User $record, ToggleTenantStatusAction $toggleTenantStatusAction): void {
                                    $updatedTenant = $toggleTenantStatusAction->handle($record);

                                    Notification::make()
                                        ->success()
                                        ->title($updatedTenant->status === UserStatus::ACTIVE
                                            ? __('admin.tenants.messages.tenant_reactivated')
                                            : __('admin.tenants.messages.tenant_deactivated'))
                                        ->send();
                                }),
                            DeleteAction::make()
                                ->label(__('admin.actions.delete'))
                                ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record))
                                ->authorize(fn (User $record): bool => TenantResource::canDelete($record))
                                ->disabled(fn (User $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                                ->tooltip(fn (User $record): ?string => $record->adminDeletionBlockedReason()),
                        ]
                        : []
                ),
            ])
            ->searchPlaceholder(__('admin.tenants.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    /**
     * @return array<int, string>
     */
    private static function propertyFilterOptions(): array
    {
        $query = Property::query()
            ->select(['id', 'organization_id', 'name'])
            ->orderBy('name')
            ->orderBy('id');

        $organizationId = app(OrganizationContext::class)->currentOrganizationId();
        $user = static::currentUser();

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        } elseif (! ($user instanceof User && $user->isSuperadmin())) {
            $query->whereKey(-1);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Property $property): array => [$property->id => $property->displayName()])
            ->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function applyAttentionQuery(Builder $query): Builder
    {
        $portalStatus = request()->query('portal_status');

        if (is_string($portalStatus) && $portalStatus !== '') {
            $query = match ($portalStatus) {
                PortalAccessStatus::NOT_INVITED->value => $query
                    ->where('portal_access_enabled', false)
                    ->whereDoesntHave('tenantInvitations'),
                PortalAccessStatus::INVITED->value => $query
                    ->whereHas('tenantInvitations', fn (Builder $invitationQuery): Builder => $invitationQuery->pending()),
                PortalAccessStatus::INVITATION_EXPIRED->value => $query
                    ->whereHas('tenantInvitations', fn (Builder $invitationQuery): Builder => $invitationQuery
                        ->whereNull('accepted_at')
                        ->whereNull('revoked_at')
                        ->where('expires_at', '<', now())),
                PortalAccessStatus::ACTIVE->value => $query
                    ->where('status', UserStatus::ACTIVE)
                    ->where('portal_access_enabled', true),
                PortalAccessStatus::DISABLED->value => $query
                    ->where('status', UserStatus::ACTIVE)
                    ->where('portal_access_enabled', false),
                default => $query,
            };
        }

        $attention = request()->query('attention');

        if (! is_string($attention) || $attention === '') {
            return $query;
        }

        return match ($attention) {
            'no_contract' => $query
                ->whereDoesntHave('rentalContracts', fn (Builder $contractQuery): Builder => $contractQuery->active()),
            'contracts_expiring', 'contracts_expiring_30' => $query
                ->whereHas('rentalContracts', fn (Builder $contractQuery): Builder => $contractQuery
                    ->active()
                    ->whereDate('end_date', '>=', today())
                    ->whereDate('end_date', '<=', today()->addDays(30))),
            'contracts_expiring_14' => $query
                ->whereHas('rentalContracts', fn (Builder $contractQuery): Builder => $contractQuery
                    ->active()
                    ->whereDate('end_date', '>=', today())
                    ->whereDate('end_date', '<=', today()->addDays(14))),
            'contracts_expired' => $query
                ->whereHas('rentalContracts', fn (Builder $contractQuery): Builder => $contractQuery
                    ->where(function (Builder $expiredQuery): void {
                        $expiredQuery
                            ->where('status', RentalContractStatus::EXPIRED)
                            ->orWhere(fn (Builder $activeQuery): Builder => $activeQuery
                                ->active()
                                ->whereDate('end_date', '<', today()));
                    })),
            'moved_out_active_contracts' => $query
                ->where('tenant_status', TenantStatus::MOVED_OUT)
                ->whereHas('rentalContracts', fn (Builder $contractQuery): Builder => $contractQuery->active()),
            'move_outs_scheduled' => $query
                ->whereHas('moveOutProcesses', fn (Builder $moveOutQuery): Builder => $moveOutQuery
                    ->whereIn('status', MoveOutProcessStatus::openValues())),
            'final_readings_pending' => $query
                ->whereHas('moveOutProcesses', fn (Builder $moveOutQuery): Builder => $moveOutQuery
                    ->whereIn('status', MoveOutProcessStatus::openValues())
                    ->where('final_readings_required', true)
                    ->whereNull('final_readings_completed_at')),
            'final_invoices_pending' => $query
                ->whereHas('moveOutProcesses', fn (Builder $moveOutQuery): Builder => $moveOutQuery
                    ->whereIn('status', MoveOutProcessStatus::openValues())
                    ->whereNull('final_invoice_id')),
            'moved_out_unpaid_balance' => $query
                ->where('tenant_status', TenantStatus::MOVED_OUT)
                ->whereHas('tenantInvoices', fn (Builder $invoiceQuery): Builder => $invoiceQuery
                    ->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::VOID])),
            default => $query,
        };
    }

    private static function canSendInvitation(User $record): bool
    {
        return TenantResource::canEdit($record)
            && ! $record->canAccessTenantPortal()
            && ! $record->latestTenantInvitationRecord()?->isPending();
    }

    private static function canResendInvitation(User $record): bool
    {
        $invitation = $record->latestTenantInvitationRecord();

        return TenantResource::canEdit($record)
            && $invitation instanceof OrganizationInvitation
            && ! $invitation->isAccepted()
            && ! $record->canAccessTenantPortal();
    }

    private static function canCopyInvitationLink(User $record): bool
    {
        return TenantResource::canEdit($record)
            && ! $record->canAccessTenantPortal();
    }

    private static function canRevokeInvitation(User $record): bool
    {
        return TenantResource::canEdit($record)
            && $record->latestTenantInvitationRecord()?->isPending();
    }

    private static function canEnablePortalAccess(User $record): bool
    {
        return TenantResource::canEdit($record)
            && $record->status === UserStatus::ACTIVE
            && ! $record->portal_access_enabled;
    }

    private static function canDisablePortalAccess(User $record): bool
    {
        return TenantResource::canEdit($record)
            && $record->portal_access_enabled;
    }

    private static function invitationStatusLabel(?OrganizationInvitation $invitation): string
    {
        return $invitation?->invitationStatus()->getLabel() ?? __('admin.tenants.empty.not_invited');
    }

    private static function portalStatusColor(PortalAccessStatus $status): string
    {
        return match ($status) {
            PortalAccessStatus::ACTIVE => 'success',
            PortalAccessStatus::INVITED => 'info',
            PortalAccessStatus::INVITATION_EXPIRED => 'warning',
            PortalAccessStatus::DISABLED => 'danger',
            PortalAccessStatus::NOT_INVITED => 'gray',
        };
    }

    private static function invitationStatusColor(?InvitationStatus $status): string
    {
        return match ($status) {
            InvitationStatus::ACCEPTED => 'success',
            InvitationStatus::PENDING => 'info',
            InvitationStatus::EXPIRED => 'warning',
            InvitationStatus::REVOKED => 'danger',
            null => 'gray',
        };
    }

    private static function dateTime(mixed $date): string
    {
        return $date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()) ?? '—';
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_filters'),
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_filters', locale: 'lt'),
        ], 'lt', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => __('admin.actions.clear_filters', locale: 'ru'),
        ], 'ru', 'filament-tables');
    }
}
