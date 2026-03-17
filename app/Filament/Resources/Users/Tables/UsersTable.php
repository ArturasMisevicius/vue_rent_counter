<?php

namespace App\Filament\Resources\Users\Tables;

use App\Actions\Superadmin\Users\StartUserImpersonationAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->placeholder('Platform'),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->since()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                        ->all()),
                SelectFilter::make('status')
                    ->options([
                        UserStatus::ACTIVE->value => 'Active',
                        UserStatus::INACTIVE->value => 'Inactive',
                        UserStatus::SUSPENDED->value => 'Suspended',
                    ]),
                SelectFilter::make('organization')
                    ->relationship('organization', 'name', fn (Builder $query): Builder => $query
                        ->select([
                            'id',
                            'name',
                        ])),
                TernaryFilter::make('last_login')
                    ->label('Recent login')
                    ->placeholder('All users')
                    ->trueLabel('Within 30 days')
                    ->falseLabel('More than 30 days ago or never')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->loggedInWithinDays(),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $builder): void {
                            $builder
                                ->whereNull('last_login_at')
                                ->orWhere('last_login_at', '<', now()->subDays(30));
                        }),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->hidden(fn (User $record): bool => ! (auth()->user()?->can('impersonate', $record) ?? false))
                    ->action(fn (User $record) => app(StartUserImpersonationAction::class)(auth()->user(), $record)),
                DeleteAction::make()
                    ->disabled(fn (User $record): bool => ! $record->canBeDeleted()),
            ]);
    }
}
