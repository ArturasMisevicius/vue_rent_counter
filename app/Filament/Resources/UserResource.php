<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    use HasTranslatedValidation;

    protected static ?string $model = User::class;

    protected static string $translationPrefix = 'users.validation';

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return __('users.labels.users');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.administration');
    }

    // Integrate UserPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', User::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', User::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Hide from non-admin users (Requirements 9.1, 9.2, 9.3)
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('users.labels.name'))
                    ->required()
                    ->maxLength(255)
                    ->validationMessages(self::getValidationMessages('name')),

                Forms\Components\TextInput::make('email')
                    ->label(__('users.labels.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(self::getValidationMessages('email')),

                Forms\Components\TextInput::make('password')
                    ->label(__('users.labels.password'))
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->confirmed()
                    ->validationMessages(self::getValidationMessages('password')),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label(__('users.labels.password_confirmation'))
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(false)
                    ->validationMessages(self::getValidationMessages('password_confirmation')),

                Forms\Components\Select::make('role')
                    ->label(__('users.labels.role'))
                    ->options([
                        UserRole::ADMIN->value => UserRole::ADMIN->label(),
                        UserRole::MANAGER->value => UserRole::MANAGER->label(),
                        UserRole::TENANT->value => UserRole::TENANT->label(),
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        // Clear tenant_id when role is changed to admin
                        if ($state === UserRole::ADMIN->value) {
                            $set('tenant_id', null);
                        }
                    })
                    ->validationMessages(self::getValidationMessages('role')),

                // Organization name for admin role (Requirement 2.1)
                Forms\Components\TextInput::make('organization_name')
                    ->label(__('users.labels.organization_name'))
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => $get('role') === UserRole::ADMIN->value
                    )
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::ADMIN->value
                    )
                    ->validationMessages(self::getValidationMessages('organization_name')),

                // Property assignment for tenant role (Requirement 5.1, 5.2)
                Forms\Components\Select::make('property_id')
                    ->label(__('users.labels.assigned_property'))
                    ->relationship('property', 'address', function (Builder $query) {
                        // Filter properties by authenticated user's tenant_id
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('role') === UserRole::TENANT->value
                    )
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::TENANT->value
                    )
                    ->validationMessages(self::getValidationMessages('properties')),

                // Parent user (admin who created this tenant) - auto-set, display only
                Forms\Components\Select::make('parent_user_id')
                    ->label(__('users.labels.created_by_admin'))
                    ->relationship('parentUser', 'name')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Get $get, ?User $record): bool => $record && $record->parent_user_id !== null
                    ),

                // Account activation status (Requirement 7.1)
                Forms\Components\Toggle::make('is_active')
                    ->label(__('users.labels.account_active'))
                    ->default(true)
                    ->helperText(__('users.helper_text.deactivated'))
                    ->validationMessages(self::getValidationMessages('is_active')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('users.labels.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('users.labels.email'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label(__('users.labels.role'))
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::ADMIN => 'danger',
                        UserRole::MANAGER => 'warning',
                        UserRole::TENANT => 'info',
                    })
                    ->formatStateUsing(fn (?UserRole $state): ?string => $state?->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label(__('users.labels.organization'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder(__('app.common.na')),

                Tables\Columns\TextColumn::make('property.address')
                    ->label(__('users.labels.assigned_property'))
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30)
                    ->placeholder(__('app.common.na')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('users.labels.is_active'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parentUser.name')
                    ->label(__('users.labels.created_by'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder(__('app.common.na')),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('users.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Table row actions removed - use page header actions instead
            ])
            ->bulkActions([
                // Bulk actions removed for Filament 4 compatibility
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role !== UserRole::SUPERADMIN) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
