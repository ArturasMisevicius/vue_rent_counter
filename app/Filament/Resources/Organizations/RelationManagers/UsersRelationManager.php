<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Superadmin\Users\CreateOrganizationRosterUserAction;
use App\Filament\Actions\Superadmin\Users\DeleteUserAction;
use App\Filament\Actions\Superadmin\Users\ResendOrganizationUserInvitationAction;
use App\Filament\Actions\Superadmin\Users\SendUserPasswordResetAction;
use App\Filament\Actions\Superadmin\Users\UpdateOrganizationRosterUserAction;
use App\Filament\Actions\Superadmin\Users\UpdateOrganizationUserRoleAction;
use App\Filament\Actions\Superadmin\Users\UpdateUserStatusAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Organizations\Schemas\OrganizationRosterUserForm;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('superadmin.organizations.relations.users.title');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('users_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withOrganizationSummary()
                ->withOrganizationRosterSupportSummary()
                ->orderedByName())
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.organizations.relations.users.columns.name'))
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('superadmin.organizations.relations.users.columns.email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('superadmin.organizations.relations.users.columns.role'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('last_login_at')
                    ->label(__('superadmin.organizations.relations.users.columns.last_login'))
                    ->since()
                    ->placeholder(__('superadmin.organizations.relations.users.placeholders.never')),
                TextColumn::make('status')
                    ->label(__('superadmin.organizations.relations.users.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn (User $record): string => $record->canResendOrganizationInvitationFromRoster()
                        ? __('superadmin.organizations.relations.users.statuses.invited')
                        : $record->status->label()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('superadmin.users.actions.new'))
                    ->authorize(fn (): bool => auth()->user()?->can('create', User::class) ?? false)
                    ->successNotificationTitle(__('superadmin.organizations.relations.users.notifications.created'))
                    ->form(OrganizationRosterUserForm::components(passwordRequired: true))
                    ->using(function (array $data, CreateOrganizationRosterUserAction $createOrganizationRosterUserAction) use ($organization): User {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);

                        return $createOrganizationRosterUserAction->handle($organization, $data, $actor);
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('superadmin.organizations.relations.users.actions.view'))
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->label(__('superadmin.users.actions.edit'))
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->successNotificationTitle(__('superadmin.organizations.relations.users.notifications.updated'))
                    ->form(OrganizationRosterUserForm::components(passwordRequired: false, ignoreCurrentRecordEmail: true))
                    ->using(function (User $record, array $data, UpdateOrganizationRosterUserAction $updateOrganizationRosterUserAction): User {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);

                        return $updateOrganizationRosterUserAction->handle($record, $data, $actor);
                    }),
                Action::make('changeRole')
                    ->label(__('superadmin.organizations.relations.users.actions.change_role'))
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->hidden(fn (User $record): bool => ! $record->canChangeRoleFromOrganizationRoster())
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->role->value,
                    ])
                    ->form([
                        Select::make('role')
                            ->label(__('superadmin.organizations.relations.users.columns.role'))
                            ->options(self::organizationRoleOptions())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, UpdateOrganizationUserRoleAction $updateOrganizationUserRoleAction): void {
                        $updateOrganizationUserRoleAction->handle(
                            $record,
                            UserRole::from((string) $data['role']),
                        );

                        Notification::make()
                            ->title(__('superadmin.organizations.relations.users.notifications.role_updated'))
                            ->success()
                            ->send();
                    }),
                Action::make('resendInvite')
                    ->label(__('superadmin.organizations.relations.users.actions.resend_invitation'))
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->hidden(fn (User $record): bool => ! $record->canResendOrganizationInvitationFromRoster())
                    ->requiresConfirmation()
                    ->action(function (User $record, ResendOrganizationUserInvitationAction $resendOrganizationUserInvitationAction) use ($organization): void {
                        $resendOrganizationUserInvitationAction->handle($organization, $record);

                        Notification::make()
                            ->title(__('superadmin.organizations.relations.users.notifications.invitation_resent'))
                            ->success()
                            ->send();
                    }),
                Action::make('toggleUserStatus')
                    ->label(fn (User $record): string => $record->status === UserStatus::SUSPENDED
                        ? __('superadmin.organizations.relations.users.actions.reinstate')
                        : __('superadmin.organizations.relations.users.actions.suspend'))
                    ->color(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (User $record, UpdateUserStatusAction $updateUserStatusAction): void {
                        $updatedUser = $updateUserStatusAction->handle(
                            $record,
                            $record->status === UserStatus::SUSPENDED ? UserStatus::ACTIVE : UserStatus::SUSPENDED,
                        );

                        Notification::make()
                            ->title($updatedUser->status === UserStatus::SUSPENDED
                                ? __('superadmin.organizations.relations.users.notifications.suspended')
                                : __('superadmin.organizations.relations.users.notifications.reinstated'))
                            ->success()
                            ->send();
                    }),
                Action::make('resetPassword')
                    ->label(__('superadmin.organizations.relations.users.actions.reset_password'))
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, SendUserPasswordResetAction $sendUserPasswordResetAction): void {
                        $sendUserPasswordResetAction->handle($record);

                        Notification::make()
                            ->title(__('superadmin.organizations.relations.users.notifications.password_reset'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('superadmin.users.actions.delete'))
                    ->authorize(fn (User $record): bool => (auth()->user()?->can('delete', $record) ?? false) && $record->canBeDeletedFromSuperadmin())
                    ->successNotificationTitle(__('superadmin.organizations.relations.users.notifications.deleted'))
                    ->using(function (User $record, DeleteUserAction $deleteUserAction): void {
                        $deleteUserAction->handle($record);
                    }),
            ])
            ->defaultSort('name');
    }

    /**
     * @return array<string, string>
     */
    private static function organizationRoleOptions(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::SUPERADMIN)
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
            ->all();
    }
}
