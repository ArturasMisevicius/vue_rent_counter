<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Superadmin\Users\DeleteUserAction;
use App\Filament\Actions\Superadmin\Users\SendUserPasswordResetAction;
use App\Filament\Actions\Superadmin\Users\StartUserImpersonationAction;
use App\Filament\Actions\Superadmin\Users\UpdateUserStatusAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;

class UserTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.users.columns.full_name'))
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('superadmin.users.columns.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('superadmin.users.columns.role'))
                    ->badge()
                    ->state(fn (User $record): string => $record->role->label()),
                TextColumn::make('organization.name')
                    ->label(__('superadmin.users.columns.organization'))
                    ->placeholder(__('superadmin.users.placeholders.platform_user'))
                    ->url(fn (User $record): ?string => $record->organization instanceof Organization
                        ? OrganizationResource::getUrl('view', ['record' => $record->organization])
                        : null),
                TextColumn::make('last_login_at')
                    ->label(__('superadmin.users.columns.last_login'))
                    ->state(fn (User $record): string => $record->last_login_at?->format('Y-m-d H:i') ?? __('superadmin.users.placeholders.never'))
                    ->placeholder(__('superadmin.users.placeholders.never'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('superadmin.users.columns.status'))
                    ->badge()
                    ->state(fn (User $record): string => $record->status->label()),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('superadmin.users.filters.role'))
                    ->placeholder(__('superadmin.users.filters.all_roles'))
                    ->options(UserRole::options()),
                SelectFilter::make('status')
                    ->label(__('superadmin.users.filters.status'))
                    ->placeholder(__('superadmin.users.filters.all'))
                    ->options([
                        UserStatus::ACTIVE->value => UserStatus::ACTIVE->label(),
                        UserStatus::SUSPENDED->value => UserStatus::SUSPENDED->label(),
                    ]),
                SelectFilter::make('organization')
                    ->label(__('superadmin.users.filters.organization'))
                    ->placeholder(__('superadmin.users.filters.all_organizations'))
                    ->relationship(
                        'organization',
                        'name',
                        fn (Builder $query): Builder => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->orderBy('id'),
                    ),
                SelectFilter::make('last_login')
                    ->label(__('superadmin.users.filters.last_login'))
                    ->placeholder(__('superadmin.users.filters.any_time'))
                    ->options([
                        'last_7_days' => __('superadmin.users.filters.last_7_days'),
                        'last_30_days' => __('superadmin.users.filters.last_30_days'),
                        'never' => __('superadmin.users.filters.never'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'last_7_days' => $query
                                ->whereNotNull('last_login_at')
                                ->where('last_login_at', '>=', now()->subDays(7)),
                            'last_30_days' => $query
                                ->whereNotNull('last_login_at')
                                ->where('last_login_at', '>=', now()->subDays(30)),
                            'never' => $query->whereNull('last_login_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('superadmin.users.actions.view')),
                EditAction::make()
                    ->label(__('superadmin.users.actions.edit')),
                Action::make('toggleUserStatus')
                    ->label(fn (User $record): string => $record->status === UserStatus::SUSPENDED
                        ? __('superadmin.users.actions.reinstate')
                        : __('superadmin.users.actions.suspend'))
                    ->color(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'success' : 'danger')
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, UpdateUserStatusAction $updateUserStatusAction): void {
                        $targetStatus = $record->status === UserStatus::SUSPENDED
                            ? UserStatus::ACTIVE
                            : UserStatus::SUSPENDED;

                        $updatedUser = $updateUserStatusAction->handle($record, $targetStatus);

                        Notification::make()
                            ->title($updatedUser->status === UserStatus::SUSPENDED
                                ? __('superadmin.users.notifications.suspended')
                                : __('superadmin.users.notifications.reinstated'))
                            ->success()
                            ->send();
                    }),
                Action::make('resetPassword')
                    ->label(__('superadmin.users.actions.reset_password'))
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, SendUserPasswordResetAction $sendUserPasswordResetAction): void {
                        $sendUserPasswordResetAction->handle($record);

                        Notification::make()
                            ->title(__('superadmin.users.notifications.password_reset'))
                            ->success()
                            ->send();
                    }),
                Action::make('impersonateUser')
                    ->label(__('superadmin.users.actions.impersonate'))
                    ->authorize(fn (): bool => auth()->user()?->isSuperadmin() ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, StartUserImpersonationAction $startUserImpersonationAction) {
                        $impersonator = auth()->user();

                        abort_unless($impersonator instanceof User, 403);

                        $startUserImpersonationAction->handle($impersonator, $record);

                        return redirect('/app');
                    }),
                DeleteAction::make('deleteUser')
                    ->label(__('superadmin.users.actions.delete'))
                    ->using(function (User $record, DeleteUserAction $deleteUserAction): void {
                        $deleteUserAction->handle($record);
                    })
                    ->authorize(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false)
                    ->disabled(fn (User $record): bool => ! $record->canBeDeletedFromSuperadmin())
                    ->tooltip(fn (User $record): ?string => $record->superadminDeletionBlockedReason()),
            ])
            ->searchPlaceholder(__('superadmin.users.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => trans('superadmin.users.filters.clear_all', locale: 'en'),
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => trans('superadmin.users.filters.clear_all', locale: 'es'),
        ], 'es', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => trans('superadmin.users.filters.clear_all', locale: 'lt'),
        ], 'lt', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => trans('superadmin.users.filters.clear_all', locale: 'ru'),
        ], 'ru', 'filament-tables');
    }
}
