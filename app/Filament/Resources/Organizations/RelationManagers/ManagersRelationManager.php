<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\OrganizationUsers\OrganizationUserResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('superadmin.organizations.relations.managers.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('role', UserRole::MANAGER)
                ->orderedByName())
            ->columns([
                TextColumn::make('name')
                    ->label(__('superadmin.users.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('superadmin.users.fields.email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('superadmin.users.fields.status'))
                    ->badge(),
                TextColumn::make('locale')
                    ->label(__('superadmin.users.fields.locale'))
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('superadmin.organizations.relations.managers.actions.create'))
                    ->authorize(function (): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('create', User::class);
                    })
                    ->form([
                        TextInput::make('name')
                            ->label(__('superadmin.users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('superadmin.users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email'),
                        Select::make('status')
                            ->label(__('superadmin.users.fields.status'))
                            ->options(UserStatus::options())
                            ->required(),
                        Select::make('locale')
                            ->label(__('superadmin.users.fields.locale'))
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        TextInput::make('password')
                            ->label(__('superadmin.users.fields.password'))
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label(__('shell.profile.fields.password_confirmation'))
                            ->password()
                            ->required(),
                    ])
                    ->using(fn (array $data): User => User::query()->create([
                        'organization_id' => $this->getOwnerRecord()->getKey(),
                        'role' => UserRole::MANAGER,
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'status' => $data['status'],
                        'locale' => $data['locale'],
                        'password' => $data['password'],
                    ])),
            ])
            ->recordActions([
                Action::make('permissions')
                    ->label(__('admin.manager_permissions.section'))
                    ->url(function (User $record): string {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();

                        $organizationUser = OrganizationUser::query()->firstOrCreate(
                            [
                                'organization_id' => $organization->id,
                                'user_id' => $record->id,
                            ],
                            [
                                'role' => UserRole::MANAGER->value,
                                'permissions' => null,
                                'joined_at' => now(),
                                'left_at' => null,
                                'is_active' => true,
                                'invited_by' => auth()->id(),
                            ],
                        );

                        return OrganizationUserResource::getUrl('edit', ['record' => $organizationUser]);
                    }),
                ViewAction::make()
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->authorize(function (User $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('update', $record);
                    })
                    ->form([
                        TextInput::make('name')
                            ->label(__('superadmin.users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('superadmin.users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        Select::make('status')
                            ->label(__('superadmin.users.fields.status'))
                            ->options(UserStatus::options())
                            ->required(),
                        Select::make('locale')
                            ->label(__('superadmin.users.fields.locale'))
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        TextInput::make('password')
                            ->label(__('superadmin.users.fields.password'))
                            ->password()
                            ->minLength(8)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    ->using(function (User $record, array $data): User {
                        $record->update([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'status' => $data['status'],
                            'locale' => $data['locale'],
                            ...(
                                filled($data['password'] ?? null)
                                    ? ['password' => $data['password']]
                                    : []
                            ),
                        ]);

                        return $record->refresh();
                    }),
                DeleteAction::make()
                    ->authorize(function (User $record): bool {
                        $user = Auth::guard()->user();

                        return $user instanceof User
                            && Gate::forUser($user)->allows('delete', $record)
                            && $record->canBeDeletedFromSuperadmin();
                    }),
            ])
            ->defaultSort('name');
    }
}
