<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\HasTranslatedValidation;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages(self::getValidationMessages('name')),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(self::getValidationMessages('email')),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->confirmed()
                    ->validationMessages(self::getValidationMessages('password')),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(false)
                    ->validationMessages(self::getValidationMessages('password_confirmation')),

                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        UserRole::ADMIN->value => UserRole::ADMIN->label(),
                        UserRole::MANAGER->value => UserRole::MANAGER->label(),
                        UserRole::TENANT->value => UserRole::TENANT->label(),
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        // Clear tenant_id when role is changed to admin
                        if ($state === UserRole::ADMIN->value) {
                            $set('tenant_id', null);
                        }
                    })
                    ->validationMessages(self::getValidationMessages('role')),

                // Organization name for admin role (Requirement 2.1)
                Forms\Components\TextInput::make('organization_name')
                    ->label('Organization Name')
                    ->maxLength(255)
                    ->required(fn (Forms\Get $get): bool => $get('role') === UserRole::ADMIN->value
                    )
                    ->visible(fn (Forms\Get $get): bool => $get('role') === UserRole::ADMIN->value
                    )
                    ->validationMessages(self::getValidationMessages('organization_name')),

                // Property assignment for tenant role (Requirement 5.1, 5.2)
                Forms\Components\Select::make('property_id')
                    ->label('Assigned Property')
                    ->relationship('property', 'address', function (Builder $query) {
                        // Filter properties by authenticated user's tenant_id
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn (Forms\Get $get): bool => $get('role') === UserRole::TENANT->value
                    )
                    ->visible(fn (Forms\Get $get): bool => $get('role') === UserRole::TENANT->value
                    )
                    ->validationMessages(self::getValidationMessages('properties')),

                // Parent user (admin who created this tenant) - auto-set, display only
                Forms\Components\Select::make('parent_user_id')
                    ->label('Created By (Admin)')
                    ->relationship('parentUser', 'name')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Forms\Get $get, ?User $record): bool => $record && $record->parent_user_id !== null
                    ),

                // Account activation status (Requirement 7.1)
                Forms\Components\Toggle::make('is_active')
                    ->label('Account Active')
                    ->default(true)
                    ->helperText('Deactivated accounts cannot log in')
                    ->validationMessages(self::getValidationMessages('is_active')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::ADMIN => 'danger',
                        UserRole::MANAGER => 'warning',
                        UserRole::TENANT => 'info',
                    })
                    ->formatStateUsing(fn (?UserRole $state): ?string => $state?->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('property.address')
                    ->label('Assigned Property')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('N/A'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parentUser.name')
                    ->label('Created By')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
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
