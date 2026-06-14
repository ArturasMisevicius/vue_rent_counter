<?php

namespace App\Filament\Resources\OrganizationUsers\Tables;

use App\Enums\ManagerMembershipStatus;
use App\Filament\Actions\Admin\OrganizationUsers\CreateManagerInvitationLinkAction;
use App\Filament\Actions\Admin\OrganizationUsers\ResendManagerInvitationAction;
use App\Filament\Actions\Admin\OrganizationUsers\RevokeManagerInvitationAction;
use App\Filament\Actions\Admin\OrganizationUsers\ToggleManagerStatusAction;
use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class OrganizationUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('admin.organization_users.fields.name'))
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('admin.organization_users.fields.email'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label(__('admin.organization_users.fields.role'))
                    ->state(fn (OrganizationUser $record): string => $record->roleLabel())
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.organization_users.fields.status'))
                    ->state(fn (OrganizationUser $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (OrganizationUser $record): string => match ($record->status) {
                        ManagerMembershipStatus::ACTIVE => 'success',
                        ManagerMembershipStatus::INVITED => 'warning',
                        ManagerMembershipStatus::DISABLED => 'danger',
                        ManagerMembershipStatus::EXPIRED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('permissions_preset')
                    ->label(__('admin.organization_users.fields.permissions_preset'))
                    ->state(fn (OrganizationUser $record): string => static::presetLabel($record))
                    ->badge()
                    ->sortable(),
                TextColumn::make('invited_at')
                    ->label(__('admin.organization_users.fields.invited_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('accepted_at')
                    ->label(__('admin.organization_users.fields.accepted_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('user.last_login_at')
                    ->label(__('admin.organization_users.fields.last_login_at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('invitedBy.name')
                    ->label(__('admin.organization_users.fields.invited_by'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (OrganizationUser $record): bool => OrganizationUserResource::canView($record)),
                Action::make('editPermissions')
                    ->label(__('admin.organization_users.actions.edit_permissions'))
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (OrganizationUser $record): string => OrganizationUserResource::getUrl('edit', ['record' => $record]))
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('updateManagerPermissions', $record)),
                Action::make('resendInvitation')
                    ->label(__('admin.organization_users.actions.resend_invitation'))
                    ->icon('heroicon-m-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (OrganizationUser $record): bool => static::canActOnInvitation($record))
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('resendInvitation', $record))
                    ->action(fn (OrganizationUser $record): OrganizationUser => static::resendInvitation($record))
                    ->successNotificationTitle(__('admin.organization_users.notifications.invitation_resent')),
                Action::make('copyInvitationLink')
                    ->label(__('admin.organization_users.actions.copy_invitation_link'))
                    ->icon('heroicon-m-link')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.organization_users.actions.close'))
                    ->visible(fn (OrganizationUser $record): bool => static::canActOnInvitation($record))
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('copyInvitationLink', $record))
                    ->form([
                        TextInput::make('invitation_link')
                            ->label(__('admin.organization_users.fields.invitation_link'))
                            ->default(fn (OrganizationUser $record): string => static::invitationLink($record))
                            ->readOnly()
                            ->copyable(),
                    ]),
                Action::make('revokeInvitation')
                    ->label(__('admin.organization_users.actions.revoke_invitation'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (OrganizationUser $record): bool => $record->status === ManagerMembershipStatus::INVITED)
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('revokeInvitation', $record))
                    ->action(fn (OrganizationUser $record): OrganizationUser => static::revokeInvitation($record))
                    ->successNotificationTitle(__('admin.organization_users.notifications.invitation_revoked')),
                Action::make('disableManager')
                    ->label(__('admin.organization_users.actions.disable_manager'))
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (OrganizationUser $record): bool => $record->status === ManagerMembershipStatus::ACTIVE)
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('disableManager', $record))
                    ->action(fn (OrganizationUser $record): OrganizationUser => static::disableManager($record))
                    ->successNotificationTitle(__('admin.organization_users.notifications.manager_disabled')),
                Action::make('reactivateManager')
                    ->label(__('admin.organization_users.actions.reactivate_manager'))
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (OrganizationUser $record): bool => $record->status === ManagerMembershipStatus::DISABLED)
                    ->authorize(fn (OrganizationUser $record): bool => static::allows('reactivateManager', $record))
                    ->action(fn (OrganizationUser $record): OrganizationUser => static::reactivateManager($record))
                    ->successNotificationTitle(__('admin.organization_users.notifications.manager_reactivated')),
            ])
            ->toolbarActions(
                static::currentUser()?->isSuperadmin()
                    ? [
                        BulkActionGroup::make([
                            DeleteBulkAction::make('deleteSelected')
                                ->authorize(function (): bool {
                                    $user = Auth::user();

                                    return $user instanceof User
                                        && Gate::forUser($user)->allows('deleteAny', OrganizationUser::class);
                                }),
                        ]),
                    ]
                    : [],
            );
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function allows(string $ability, OrganizationUser $record): bool
    {
        $user = static::currentUser();

        return $user instanceof User
            && Gate::forUser($user)->allows($ability, $record);
    }

    private static function canActOnInvitation(OrganizationUser $record): bool
    {
        return in_array($record->status, [
            ManagerMembershipStatus::INVITED,
            ManagerMembershipStatus::EXPIRED,
        ], true);
    }

    private static function presetLabel(OrganizationUser $record): string
    {
        $preset = (string) ($record->permissions_preset ?: 'read_only');

        return ManagerPermissionCatalog::presets()[$preset]['name']
            ?? __('admin.manager_permissions.presets.custom');
    }

    private static function resendInvitation(OrganizationUser $record): OrganizationUser
    {
        $actor = static::currentUser();
        abort_unless($actor instanceof User, 403);

        app(ResendManagerInvitationAction::class)->handle($record, $actor);

        return $record->refresh();
    }

    private static function invitationLink(OrganizationUser $record): string
    {
        $actor = static::currentUser();
        abort_unless($actor instanceof User, 403);

        $invitation = app(CreateManagerInvitationLinkAction::class)->handle($record, $actor);

        return route('invitation.show', $invitation->routeToken());
    }

    private static function revokeInvitation(OrganizationUser $record): OrganizationUser
    {
        $actor = static::currentUser();
        abort_unless($actor instanceof User, 403);

        return app(RevokeManagerInvitationAction::class)->handle($record, $actor);
    }

    private static function disableManager(OrganizationUser $record): OrganizationUser
    {
        $actor = static::currentUser();
        abort_unless($actor instanceof User, 403);

        return app(ToggleManagerStatusAction::class)->disable($record, $actor);
    }

    private static function reactivateManager(OrganizationUser $record): OrganizationUser
    {
        $actor = static::currentUser();
        abort_unless($actor instanceof User, 403);

        return app(ToggleManagerStatusAction::class)->reactivate($record, $actor);
    }
}
