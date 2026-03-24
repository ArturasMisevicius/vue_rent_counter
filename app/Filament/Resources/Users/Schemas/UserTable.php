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
                    ->label('Full Name')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->state(fn (User $record): string => $record->role->label()),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->placeholder('Platform user')
                    ->url(fn (User $record): ?string => $record->organization instanceof Organization
                        ? OrganizationResource::getUrl('view', ['record' => $record->organization])
                        : null),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->state(fn (User $record): string => $record->last_login_at?->format('Y-m-d H:i') ?? 'Never')
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (User $record): string => $record->status->label()),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->placeholder('All Roles')
                    ->options(UserRole::options()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->placeholder('All')
                    ->options([
                        UserStatus::ACTIVE->value => UserStatus::ACTIVE->label(),
                        UserStatus::SUSPENDED->value => UserStatus::SUSPENDED->label(),
                    ]),
                SelectFilter::make('organization')
                    ->label('Organization')
                    ->placeholder('All Organizations')
                    ->relationship(
                        'organization',
                        'name',
                        fn (Builder $query): Builder => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->orderBy('id'),
                    ),
                SelectFilter::make('last_login')
                    ->label('Last Login')
                    ->placeholder('Any Time')
                    ->options([
                        'last_7_days' => 'Last 7 Days',
                        'last_30_days' => 'Last 30 Days',
                        'never' => 'Never',
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
                    ->label('View'),
                EditAction::make()
                    ->label('Edit'),
                Action::make('toggleUserStatus')
                    ->label(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'Reinstate' : 'Suspend')
                    ->color(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'success' : 'danger')
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, UpdateUserStatusAction $updateUserStatusAction): void {
                        $targetStatus = $record->status === UserStatus::SUSPENDED
                            ? UserStatus::ACTIVE
                            : UserStatus::SUSPENDED;

                        $updatedUser = $updateUserStatusAction->handle($record, $targetStatus);

                        Notification::make()
                            ->title($updatedUser->status === UserStatus::SUSPENDED ? 'User suspended' : 'User reinstated')
                            ->success()
                            ->send();
                    }),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, SendUserPasswordResetAction $sendUserPasswordResetAction): void {
                        $sendUserPasswordResetAction->handle($record);

                        Notification::make()
                            ->title('Password reset email sent')
                            ->success()
                            ->send();
                    }),
                Action::make('impersonateUser')
                    ->label('Impersonate')
                    ->authorize(fn (): bool => auth()->user()?->isSuperadmin() ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record, StartUserImpersonationAction $startUserImpersonationAction) {
                        $impersonator = auth()->user();

                        abort_unless($impersonator instanceof User, 403);

                        $startUserImpersonationAction->handle($impersonator, $record);

                        return redirect('/app');
                    }),
                DeleteAction::make('deleteUser')
                    ->label('Delete')
                    ->using(function (User $record, DeleteUserAction $deleteUserAction): void {
                        $deleteUserAction->handle($record);
                    })
                    ->authorize(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false)
                    ->disabled(fn (User $record): bool => ! $record->canBeDeletedFromSuperadmin())
                    ->tooltip(fn (User $record): ?string => $record->superadminDeletionBlockedReason()),
            ])
            ->searchPlaceholder('Search by name or email')
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => 'Clear All Filters',
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Limpiar todos los filtros',
        ], 'es', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Išvalyti visus filtrus',
        ], 'lt', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Очистить все фильтры',
        ], 'ru', 'filament-tables');
    }
}
