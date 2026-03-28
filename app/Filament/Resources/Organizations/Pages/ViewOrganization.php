<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Filament\Actions\Superadmin\Organizations\ForceOrganizationPlanChangeAction;
use App\Filament\Actions\Superadmin\Organizations\QueueOrganizationDataExportAction;
use App\Filament\Actions\Superadmin\Organizations\ReinstateOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\TransferOrganizationOwnershipAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    use HasDeferredRelationManagerTabBadges;

    protected static string $resource = OrganizationResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $relation = request()->query('relation');

        if (! is_numeric($relation)) {
            return;
        }

        $relationIndex = (int) $relation;

        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            $relationIndex--;
        }

        if ($relationIndex < 0) {
            return;
        }

        $relationManagerKeys = array_values(array_keys(OrganizationResource::getRelations()));
        $relationManagerKey = $relationManagerKeys[$relationIndex] ?? null;

        if (! is_string($relationManagerKey)) {
            return;
        }

        $this->activeRelationManager = $relationManagerKey;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrganizationResource::getUrl('index') => OrganizationResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->slug;
    }

    public function getContentTabLabel(): ?string
    {
        return __('superadmin.organizations.pages.overview_tab');
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('superadmin.organizations.actions.edit')),
            Action::make('suspendOrganization')
                ->label(__('superadmin.organizations.actions.suspend'))
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->permitsAccess())
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('suspend', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __('superadmin.organizations.modals.suspend', [
                    'name' => $this->record->name,
                ]))
                ->action(function (SuspendOrganizationAction $suspendOrganizationAction): void {
                    $suspendOrganizationAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.suspended'))
                        ->success()
                        ->send();
                }),
            Action::make('reinstateOrganization')
                ->label(__('superadmin.organizations.actions.reinstate'))
                ->color('success')
                ->visible(fn (): bool => $this->record->status === OrganizationStatus::SUSPENDED)
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('reinstate', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => __('superadmin.organizations.modals.reinstate', [
                    'name' => $this->record->name,
                ]))
                ->action(function (ReinstateOrganizationAction $reinstateOrganizationAction): void {
                    $reinstateOrganizationAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.reinstated'))
                        ->success()
                        ->send();
                }),
            Action::make('forcePlanChange')
                ->label(__('superadmin.organizations.actions.force_plan_change'))
                ->slideOver()
                ->visible(fn (): bool => $this->authenticatedUser()?->isSuperadmin() ?? false)
                ->authorize(fn (): bool => $this->authenticatedUser()?->isSuperadmin() ?? false)
                ->schema([
                    Select::make('plan')
                        ->label(__('superadmin.organizations.form.fields.plan'))
                        ->options(SubscriptionPlan::options())
                        ->required(),
                    Textarea::make('reason')
                        ->label(__('superadmin.organizations.form.fields.change_reason'))
                        ->required()
                        ->rows(4)
                        ->maxLength(500),
                ])
                ->action(function (array $data, ForceOrganizationPlanChangeAction $forceOrganizationPlanChangeAction): void {
                    $forceOrganizationPlanChangeAction->handle(
                        $this->record,
                        SubscriptionPlan::from((string) $data['plan']),
                        $data['reason'],
                    );

                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.plan_changed'))
                        ->success()
                        ->send();
                }),
            Action::make('transferOwnership')
                ->label(__('superadmin.organizations.actions.transfer_ownership'))
                ->slideOver()
                ->visible(fn (): bool => $this->authenticatedUser()?->isSuperadmin() ?? false)
                ->authorize(fn (): bool => $this->authenticatedUser()?->isSuperadmin() ?? false)
                ->schema([
                    Select::make('new_owner_user_id')
                        ->label(__('superadmin.organizations.form.fields.new_owner_user_id'))
                        ->options(fn (): array => $this->ownershipCandidateOptions())
                        ->searchable()
                        ->required(),
                    Textarea::make('reason')
                        ->label(__('superadmin.organizations.form.fields.change_reason'))
                        ->required()
                        ->rows(4)
                        ->maxLength(500),
                ])
                ->action(function (array $data, TransferOrganizationOwnershipAction $transferOrganizationOwnershipAction): void {
                    $newOwner = User::query()
                        ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'email_verified_at'])
                        ->findOrFail((int) $data['new_owner_user_id']);

                    $transferOrganizationOwnershipAction->handle(
                        $this->record,
                        $newOwner,
                        $data['reason'],
                    );

                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.ownership_transferred'))
                        ->success()
                        ->send();
                }),
            Action::make('sendNotification')
                ->label(__('superadmin.organizations.actions.send_notification'))
                ->slideOver()
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('update', $this->record) ?? false)
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
                ->action(function (array $data, SendOrganizationNotificationAction $sendOrganizationNotificationAction): void {
                    $sendOrganizationNotificationAction->handle(
                        $this->record,
                        $data['title'],
                        $data['body'],
                        $data['severity'],
                    );

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.queued'))
                        ->success()
                        ->send();
                }),
            Action::make('impersonateAdmin')
                ->label(__('superadmin.organizations.actions.impersonate_admin'))
                ->icon('heroicon-o-user-circle')
                ->visible(fn (): bool => $this->record->status->permitsAccess())
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('impersonate', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(__('superadmin.organizations.modals.impersonate'))
                ->action(function (StartOrganizationImpersonationAction $startOrganizationImpersonationAction): void {
                    $admin = $this->resolvePrimaryAdmin();
                    $impersonator = $this->authenticatedUser();

                    abort_if($admin === null, 404, __('superadmin.organizations.messages.no_primary_admin'));
                    abort_if($impersonator === null, 403);

                    $startOrganizationImpersonationAction->handle($impersonator, $admin);

                    $this->redirect('/app');
                }),
            Action::make('exportData')
                ->label(__('superadmin.organizations.actions.export_data'))
                ->slideOver()
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('view', $this->record) ?? false)
                ->modalDescription(__('superadmin.organizations.modals.export'))
                ->schema([
                    Textarea::make('reason')
                        ->label(__('superadmin.organizations.form.fields.export_reason'))
                        ->required()
                        ->rows(4)
                        ->maxLength(500),
                ])
                ->action(function (array $data, QueueOrganizationDataExportAction $queueOrganizationDataExportAction): void {
                    $queueOrganizationDataExportAction->handle(
                        $this->record,
                        $data['reason'],
                    );

                    Notification::make()
                        ->title(__('superadmin.organizations.notifications.export_queued'))
                        ->success()
                        ->send();
                }),
        ];
    }

    private function refreshRecord(): void
    {
        $this->record = OrganizationResource::getEloquentQuery()->findOrFail($this->record->getKey());
    }

    private function resolvePrimaryAdmin(): ?User
    {
        $owner = $this->record->owner;

        if ($owner instanceof User && $owner->isAdmin()) {
            return $owner;
        }

        return $this->record->users()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'password', 'remember_token'])
            ->where('role', UserRole::ADMIN)
            ->orderedByName()
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function ownershipCandidateOptions(): array
    {
        return $this->record->ownershipCandidates()
            ->select(['id', 'organization_id', 'name', 'email', 'email_verified_at'])
            ->orderedByName()
            ->get()
            ->filter(fn (User $user): bool => $user->email_verified_at !== null)
            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
            ->all();
    }

    private function authenticatedUser(): ?User
    {
        $user = auth()->guard()->user();

        return $user instanceof User ? $user : null;
    }
}
