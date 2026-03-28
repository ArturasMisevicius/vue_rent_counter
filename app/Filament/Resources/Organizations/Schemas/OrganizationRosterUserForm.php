<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class OrganizationRosterUserForm
{
    /**
     * @return array<int, Component>
     */
    public static function components(bool $passwordRequired, bool $ignoreCurrentRecordEmail = false): array
    {
        return [
            TextInput::make('name')
                ->label(__('superadmin.users.fields.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label(__('superadmin.users.fields.email'))
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(table: User::class, column: 'email', ignoreRecord: $ignoreCurrentRecordEmail),
            Select::make('role')
                ->label(__('superadmin.users.fields.role'))
                ->options(self::roleOptions())
                ->required(),
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
                ->required($passwordRequired)
                ->minLength(8)
                ->dehydrated(fn (?string $state): bool => filled($state)),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::SUPERADMIN)
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
            ->all();
    }
}
