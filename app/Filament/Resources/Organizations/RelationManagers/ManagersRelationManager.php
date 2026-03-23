<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
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

class ManagersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Managers';
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('role', UserRole::MANAGER)
                ->orderedByName())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('locale')
                    ->label('Locale')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create manager')
                    ->form([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email'),
                        Select::make('status')
                            ->label('Status')
                            ->options(UserStatus::options())
                            ->required(),
                        Select::make('locale')
                            ->label('Locale')
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label('Confirm password')
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
                ViewAction::make()
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        Select::make('status')
                            ->label('Status')
                            ->options(UserStatus::options())
                            ->required(),
                        Select::make('locale')
                            ->label('Locale')
                            ->options(config('tenanto.locales', []))
                            ->required(),
                        TextInput::make('password')
                            ->label('Password')
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
                    ->authorize(fn (User $record): bool => $record->canBeDeletedFromSuperadmin()),
            ])
            ->defaultSort('name');
    }
}
