<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

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
                    ->validationMessages([
                        'required' => 'The name is required.',
                        'max' => 'The name cannot exceed 255 characters.',
                    ]),
                
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'The email is required.',
                        'email' => 'The email must be a valid email address.',
                        'unique' => 'This email address is already registered.',
                        'max' => 'The email cannot exceed 255 characters.',
                    ]),
                
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->confirmed()
                    ->validationMessages([
                        'required' => 'The password is required.',
                        'min' => 'Password must be at least 8 characters.',
                        'confirmed' => 'Password confirmation does not match.',
                    ]),
                
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(false)
                    ->validationMessages([
                        'required' => 'Password confirmation is required.',
                    ]),
                
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options([
                        UserRole::ADMIN->value => 'Admin',
                        UserRole::MANAGER->value => 'Manager',
                        UserRole::TENANT->value => 'Tenant',
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
                    ->validationMessages([
                        'required' => 'The role is required.',
                    ]),
                
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->options(Tenant::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(fn (Forms\Get $get): bool => 
                        in_array($get('role'), [UserRole::MANAGER->value, UserRole::TENANT->value])
                    )
                    ->hidden(fn (Forms\Get $get): bool => 
                        $get('role') === UserRole::ADMIN->value
                    )
                    ->validationMessages([
                        'required' => 'The tenant is required for manager and tenant roles.',
                        'exists' => 'The selected tenant does not exist.',
                    ]),
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
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->formatStateUsing(fn ($state) => $state ? Tenant::find($state)?->name : 'N/A')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
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
