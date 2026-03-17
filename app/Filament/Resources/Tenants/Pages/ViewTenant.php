<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Actions\Auth\ResendOrganizationInvitationAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\OrganizationInvitation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            TenantResource::getUrl('index') => TenantResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.tenants.view_title');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...(
                TenantResource::shouldInterceptGraceEditAction()
                    ? [
                        TenantResource::makeSubscriptionInfoAction(
                            name: 'edit',
                            resource: 'tenants',
                            label: __('filament-actions::edit.single.label', [
                                'label' => TenantResource::getModelLabel(),
                            ]),
                        ),
                    ]
                    : (
                        TenantResource::canEdit($this->record)
                            ? [EditAction::make()]
                            : []
                    )
            ),
            ...(
                TenantResource::canMutateSubscriptionScopedRecords()
                    ? [
                        Action::make('resendInvitation')
                            ->label(__('admin.tenants.actions.resend_invitation'))
                            ->visible(fn (): bool => $this->record->status === UserStatus::INACTIVE && $this->latestInvitation() !== null)
                            ->requiresConfirmation()
                            ->action(function (ResendOrganizationInvitationAction $resendOrganizationInvitationAction): void {
                                $invitation = $this->latestInvitation();

                                abort_if($invitation === null, 404);

                                $resendOrganizationInvitationAction->handle(auth()->user(), $invitation);

                                Notification::make()
                                    ->title(__('admin.tenants.messages.invitation_resent'))
                                    ->success()
                                    ->send();
                            }),
                    ]
                    : []
            ),
        ];
    }

    private function latestInvitation(): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->select([
                'id',
                'organization_id',
                'inviter_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'expires_at',
                'accepted_at',
            ])
            ->where('organization_id', $this->record->organization_id)
            ->where('email', $this->record->email)
            ->where('role', UserRole::TENANT)
            ->latest('id')
            ->first();
    }
}
