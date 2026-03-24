<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Users';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('users_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withOrganizationSummary()->orderedByName())
            ->columns([
                TextColumn::make('name')
                    ->label('Full Name')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->since()
                    ->placeholder('Never'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                Action::make('toggleUserStatus')
                    ->label(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'Reinstate' : 'Suspend')
                    ->color(fn (User $record): string => $record->status === UserStatus::SUSPENDED ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (User $record): void {
                        $record->update([
                            'status' => $record->status === UserStatus::SUSPENDED ? UserStatus::ACTIVE : UserStatus::SUSPENDED,
                        ]);

                        Notification::make()
                            ->title($record->status === UserStatus::SUSPENDED ? 'User suspended' : 'User reinstated')
                            ->success()
                            ->send();
                    }),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->authorize(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        Password::sendResetLink([
                            'email' => $record->email,
                        ]);

                        Notification::make()
                            ->title('Password reset email sent')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('name');
    }
}
