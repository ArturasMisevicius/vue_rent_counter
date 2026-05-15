<?php

namespace App\Filament\Resources\OrganizationUsers\Tables;

use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
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
                    ->label(__('superadmin.relation_resources.organization_users.fields.user'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('superadmin.relation_resources.organization_users.fields.role'))
                    ->state(fn (OrganizationUser $record): string => $record->roleLabel())
                    ->badge()
                    ->searchable(),
                TextColumn::make('joined_at')
                    ->label(__('superadmin.relation_resources.organization_users.fields.joined_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('left_at')
                    ->label(__('superadmin.relation_resources.organization_users.fields.left_at'))
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('superadmin.relation_resources.organization_users.fields.is_active'))
                    ->boolean(),
                TextColumn::make('inviter.name')
                    ->label(__('superadmin.relation_resources.organization_users.fields.inviter'))
                    ->sortable(),
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
                EditAction::make()
                    ->authorize(fn (OrganizationUser $record): bool => OrganizationUserResource::canEdit($record)),
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
}
