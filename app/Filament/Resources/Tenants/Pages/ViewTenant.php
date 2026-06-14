<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Enums\PortalAccessAfterMoveOut;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\TenantMoveOut\CancelTenantMoveOut;
use App\Filament\Actions\Admin\TenantMoveOut\CompleteTenantMoveOut;
use App\Filament\Actions\Admin\TenantMoveOut\GenerateFinalInvoice;
use App\Filament\Actions\Admin\TenantMoveOut\RecordFinalMoveOutReadings;
use App\Filament\Actions\Admin\TenantMoveOut\ScheduleTenantMoveOut;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\DisableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\EnableTenantPortalAccess;
use App\Filament\Actions\Admin\Tenants\ResendTenantInvitation;
use App\Filament\Actions\Admin\Tenants\RevokeTenantInvitation;
use App\Filament\Actions\Admin\Tenants\SendTenantInvitation;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Meter;
use App\Models\MoveOutProcess;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class ViewTenant extends ViewRecord
{
    use HasDeferredRelationManagerTabBadges;

    protected static string $resource = TenantResource::class;

    private ?OrganizationInvitation $latestInvitation = null;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            TenantResource::getUrl('index') => TenantResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->email;
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.tenants.tabs.profile');
    }

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('tenant.invitation'),
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
                        TenantResource::canEdit($this->record)
                            ? [
                                Action::make('edit')
                                    ->label(__('admin.tenants.actions.edit_tenant'))
                                    ->url(fn (): string => TenantResource::getUrl('edit', [
                                        'record' => $this->record,
                                    ]))
                                    ->button(),
                            ]
                            : []
                    )
            ),
            ...(
                TenantResource::canEdit($this->record)
                    ? [
                        Action::make('sendInvitation')
                            ->label(__('admin.tenants.actions.send_invitation'))
                            ->icon('heroicon-m-envelope')
                            ->visible(fn (): bool => $this->canSendInvitation())
                            ->action(function (SendTenantInvitation $sendTenantInvitation): void {
                                $actor = auth()->user();

                                abort_if(! $actor instanceof User, 403);

                                $this->latestInvitation = $sendTenantInvitation->handle($actor, $this->record);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.tenants.messages.invitation_sent', ['email' => $this->record->email]))
                                    ->send();
                            }),
                        Action::make('resendInvitation')
                            ->label(__('admin.tenants.actions.resend_invitation'))
                            ->icon('heroicon-m-arrow-path')
                            ->visible(fn (): bool => $this->canResendInvitation())
                            ->action(function (ResendTenantInvitation $resendTenantInvitation): void {
                                $actor = auth()->user();

                                abort_if(! $actor instanceof User, 403);

                                $this->latestInvitation = $resendTenantInvitation->handle($actor, $this->record);
                                $this->refreshRecord();

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
                            ->mountUsing(function (Schema $form): void {
                                $actor = auth()->user();

                                abort_if(! $actor instanceof User, 403);

                                $this->latestInvitation = app(SendTenantInvitation::class)->handle(
                                    $actor,
                                    $this->record,
                                    sendEmail: false,
                                );

                                $this->refreshRecord();

                                $form->fill([
                                    'invitation_link' => route('invitation.show', $this->latestInvitation->acceptanceToken),
                                ]);
                            })
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('admin.actions.close'))
                            ->visible(fn (): bool => $this->canCopyInvitationLink()),
                        Action::make('revokeInvitation')
                            ->label(__('admin.tenants.actions.revoke_invitation'))
                            ->icon('heroicon-m-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->visible(fn (): bool => $this->canRevokeInvitation())
                            ->action(function (RevokeTenantInvitation $revokeTenantInvitation): void {
                                $actor = auth()->user();
                                $invitation = $this->latestInvitation();

                                abort_if(! $actor instanceof User || ! $invitation instanceof OrganizationInvitation, 403);

                                $this->latestInvitation = $revokeTenantInvitation->handle($actor, $invitation);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.tenants.messages.invitation_revoked'))
                                    ->send();
                            }),
                        Action::make('enablePortalAccess')
                            ->label(__('admin.tenants.actions.enable_portal_access'))
                            ->icon('heroicon-m-lock-open')
                            ->color('success')
                            ->visible(fn (): bool => $this->canEnablePortalAccess())
                            ->action(function (EnableTenantPortalAccess $enableTenantPortalAccess): void {
                                $actor = auth()->user();

                                abort_if(! $actor instanceof User, 403);

                                $enableTenantPortalAccess->handle($actor, $this->record);
                                $this->refreshRecord();

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
                            ->visible(fn (): bool => $this->canDisablePortalAccess())
                            ->action(function (DisableTenantPortalAccess $disableTenantPortalAccess): void {
                                $actor = auth()->user();

                                abort_if(! $actor instanceof User, 403);

                                $disableTenantPortalAccess->handle($actor, $this->record);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.tenants.messages.portal_access_disabled'))
                                    ->send();
                            }),
                        Action::make('assignProperty')
                            ->label($this->record->currentPropertyAssignment === null
                                ? __('admin.tenants.actions.assign_to_property')
                                : __('admin.tenants.actions.reassign_property'))
                            ->slideOver()
                            ->modalDescription($this->record->currentPropertyAssignment === null
                                ? null
                                : __('admin.tenants.messages.reassign_property_warning'))
                            ->schema([
                                Select::make('property_id')
                                    ->label(__('admin.tenants.fields.property'))
                                    ->options(fn (): array => $this->availablePropertyOptions())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (mixed $state, callable $set): void {
                                        $property = $this->findProperty($state);

                                        $set('unit_area_sqm', $property?->floor_area_sqm !== null
                                            ? (float) $property->floor_area_sqm
                                            : null);
                                    }),
                                TextInput::make('unit_area_sqm')
                                    ->label(__('admin.tenants.fields.unit_area_sqm'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(fn (): ?float => $this->record->currentPropertyAssignment?->unit_area_sqm !== null
                                        ? (float) $this->record->currentPropertyAssignment->unit_area_sqm
                                        : ($this->record->currentProperty?->floor_area_sqm !== null
                                            ? (float) $this->record->currentProperty->floor_area_sqm
                                            : null)),
                                DatePicker::make('move_in_date')
                                    ->label(__('admin.tenants.fields.move_in_date'))
                                    ->default(today()->toDateString())
                                    ->required(),
                            ])
                            ->action(function (array $data, AssignTenantToPropertyAction $assignTenantToPropertyAction): void {
                                $property = $this->findProperty($data['property_id'] ?? null);

                                abort_if($property === null, 404);

                                $assignTenantToPropertyAction->handle(
                                    $property,
                                    $this->record,
                                    isset($data['unit_area_sqm']) ? (float) $data['unit_area_sqm'] : null,
                                    CarbonImmutable::parse((string) $data['move_in_date']),
                                );

                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.tenants.messages.property_reassigned'))
                                    ->send();
                            }),
                        Action::make('scheduleMoveOut')
                            ->label(__('admin.move_out.actions.schedule'))
                            ->icon('heroicon-m-calendar-days')
                            ->color('warning')
                            ->slideOver()
                            ->schema(self::moveOutWizardSchema())
                            ->visible(fn (): bool => $this->currentAssignmentCanMoveOut())
                            ->action(function (array $data, ScheduleTenantMoveOut $scheduleTenantMoveOut): void {
                                $actor = auth()->user();
                                $assignment = $this->record->currentPropertyAssignment;

                                abort_if(! $actor instanceof User || ! $assignment instanceof PropertyAssignment, 403);

                                $scheduleTenantMoveOut->handle($actor, $assignment, $data);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.move_out.messages.scheduled'))
                                    ->send();
                            }),
                        Action::make('generateFinalInvoice')
                            ->label(__('admin.move_out.actions.generate_final_invoice'))
                            ->icon('heroicon-m-document-text')
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->action(function (GenerateFinalInvoice $generateFinalInvoice): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $generateFinalInvoice->handle($actor, $process, [
                                    'allow_without_final_readings' => ! $process->final_readings_required,
                                ]);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.move_out.messages.final_invoice_generated'))
                                    ->send();
                            }),
                        Action::make('recordFinalReadings')
                            ->label(__('admin.move_out.actions.record_final_readings'))
                            ->icon('heroicon-m-pencil-square')
                            ->slideOver()
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->schema([
                                Repeater::make('readings')
                                    ->label(__('admin.move_out.actions.record_final_readings'))
                                    ->schema([
                                        Select::make('meter_id')
                                            ->label(__('admin.meters.singular'))
                                            ->options(fn (): array => $this->finalReadingMeterOptions())
                                            ->searchable()
                                            ->required(),
                                        TextInput::make('reading_value')
                                            ->label(__('admin.meter_readings.fields.reading_value'))
                                            ->numeric()
                                            ->required(),
                                        DatePicker::make('reading_date')
                                            ->label(__('admin.meter_readings.fields.reading_date'))
                                            ->default(fn (): ?string => $this->currentMoveOutProcess()?->move_out_date?->toDateString())
                                            ->required(),
                                        Textarea::make('notes')
                                            ->label(__('admin.meter_readings.fields.notes'))
                                            ->rows(2),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(1)
                                    ->required(),
                            ])
                            ->action(function (array $data, RecordFinalMoveOutReadings $recordFinalMoveOutReadings): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $recordFinalMoveOutReadings->handle($actor, $process, $data['readings'] ?? []);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.move_out.messages.final_readings_recorded'))
                                    ->send();
                            }),
                        Action::make('cancelMoveOut')
                            ->label(__('admin.move_out.actions.cancel'))
                            ->icon('heroicon-m-x-circle')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->action(function (CancelTenantMoveOut $cancelTenantMoveOut): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $cancelTenantMoveOut->handle($actor, $process);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.move_out.messages.cancelled'))
                                    ->send();
                            }),
                        Action::make('completeMoveOut')
                            ->label(__('admin.move_out.actions.complete'))
                            ->icon('heroicon-m-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->schema([
                                Toggle::make('allow_without_final_readings')
                                    ->label(__('admin.move_out.fields.allow_without_final_readings')),
                                Toggle::make('allow_without_final_invoice')
                                    ->label(__('admin.move_out.fields.allow_without_final_invoice')),
                            ])
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->action(function (array $data, CompleteTenantMoveOut $completeTenantMoveOut): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $completeTenantMoveOut->handle($actor, $process, $data);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.move_out.messages.completed'))
                                    ->send();
                            }),
                        Action::make('toggleStatus')
                            ->label($this->record->status === UserStatus::ACTIVE
                                ? __('admin.tenants.actions.deactivate')
                                : __('admin.tenants.actions.reactivate'))
                            ->color($this->record->status === UserStatus::ACTIVE ? 'warning' : 'success')
                            ->requiresConfirmation()
                            ->modalDescription($this->record->status === UserStatus::ACTIVE
                                ? __('admin.tenants.messages.deactivate_confirmation')
                                : __('admin.tenants.messages.reactivate_confirmation'))
                            ->action(function (ToggleTenantStatusAction $toggleTenantStatusAction): void {
                                $updatedTenant = $toggleTenantStatusAction->handle($this->record);
                                $this->refreshRecord();

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
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availablePropertyOptions(): array
    {
        return Property::query()
            ->availableForTenantAssignment($this->record->organization_id, $this->record->id)
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private function findProperty(mixed $propertyId): ?Property
    {
        if (blank($propertyId)) {
            return null;
        }

        return Property::query()
            ->availableForTenantAssignment($this->record->organization_id, $this->record->id)
            ->find($propertyId);
    }

    /**
     * @return array<int, Wizard>
     */
    private static function moveOutWizardSchema(): array
    {
        return [
            Wizard::make([
                Step::make(__('admin.move_out.wizard.steps.schedule'))
                    ->schema([
                        DatePicker::make('move_out_date')
                            ->label(__('admin.tenants.fields.move_out_date'))
                            ->default(today()->toDateString())
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('admin.move_out.fields.reason'))
                            ->rows(3),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.final_readings'))
                    ->schema([
                        Toggle::make('final_readings_required')
                            ->label(__('admin.move_out.fields.final_readings_required'))
                            ->default(true),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.outstanding_balance'))
                    ->schema([
                        Textarea::make('internal_note')
                            ->label(__('admin.move_out.fields.internal_note'))
                            ->rows(3),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.contract_documents'))
                    ->schema([
                        Textarea::make('contract_note')
                            ->label(__('admin.move_out.fields.contract_note'))
                            ->rows(2),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.portal_access'))
                    ->schema([
                        Select::make('portal_access_after_move_out')
                            ->label(__('admin.move_out.fields.portal_access_after_move_out'))
                            ->options(PortalAccessAfterMoveOut::options())
                            ->default(PortalAccessAfterMoveOut::KEEP_HISTORICAL_ACCESS->value)
                            ->required(),
                    ]),
            ]),
        ];
    }

    private function currentAssignmentCanMoveOut(): bool
    {
        return $this->record->currentPropertyAssignment instanceof PropertyAssignment
            && $this->record->currentPropertyAssignment->status === PropertyAssignmentStatus::ACTIVE
            && ! ($this->currentMoveOutProcess() instanceof MoveOutProcess);
    }

    private function currentMoveOutProcess(): ?MoveOutProcess
    {
        $assignment = $this->record->currentPropertyAssignment;

        if (! $assignment instanceof PropertyAssignment) {
            return null;
        }

        return $assignment->activeMoveOutProcess()
            ->select(['id', 'organization_id', 'tenant_id', 'property_id', 'property_assignment_id', 'status', 'move_out_date', 'final_readings_required', 'final_readings_completed_at', 'final_invoice_id', 'contract_id', 'portal_access_after_move_out', 'reason'])
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function finalReadingMeterOptions(): array
    {
        $assignment = $this->record->currentPropertyAssignment;

        if (! $assignment instanceof PropertyAssignment) {
            return [];
        }

        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier'])
            ->forOrganization((int) $assignment->organization_id)
            ->forProperty((int) $assignment->property_id)
            ->active()
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Meter $meter): array => [
                $meter->id => $meter->displayName(),
            ])
            ->all();
    }

    private function refreshRecord(): void
    {
        $this->record = TenantResource::getRecordRouteBindingEloquentQuery()
            ->findOrFail($this->record->getKey());
    }

    private function canResendInvitation(): bool
    {
        $invitation = $this->latestInvitation();

        return $invitation instanceof OrganizationInvitation
            && ! $invitation->isAccepted()
            && ! $this->record->canAccessTenantPortal();
    }

    private function canSendInvitation(): bool
    {
        return ! $this->record->canAccessTenantPortal()
            && ! $this->latestInvitation()?->isPending();
    }

    private function canCopyInvitationLink(): bool
    {
        return ! $this->record->canAccessTenantPortal();
    }

    private function canRevokeInvitation(): bool
    {
        return $this->latestInvitation()?->isPending() ?? false;
    }

    private function canEnablePortalAccess(): bool
    {
        return $this->record->status === UserStatus::ACTIVE
            && ! $this->record->portal_access_enabled;
    }

    private function canDisablePortalAccess(): bool
    {
        return $this->record->portal_access_enabled;
    }

    private function latestInvitation(): ?OrganizationInvitation
    {
        if ($this->latestInvitation instanceof OrganizationInvitation) {
            return $this->latestInvitation;
        }

        $organizationId = $this->record->organization_id;

        if ($organizationId === null || blank($this->record->email)) {
            return null;
        }

        $this->latestInvitation = OrganizationInvitation::query()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'inviter_user_id',
                'invited_by_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'token_hash',
                'sent_at',
                'expires_at',
                'accepted_at',
                'revoked_at',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($organizationId)
            ->where(function ($query): void {
                $query
                    ->where('tenant_id', $this->record->id)
                    ->orWhere(function ($query): void {
                        $query
                            ->where('email', $this->record->email)
                            ->where('role', $this->record->role);
                    });
            })
            ->latestSentFirst()
            ->first();

        return $this->latestInvitation;
    }
}
