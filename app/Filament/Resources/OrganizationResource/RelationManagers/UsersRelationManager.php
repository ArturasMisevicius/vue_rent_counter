<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = null;

    protected static BackedEnum|string|null $icon = 'heroicon-o-users';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('users.labels.name'))
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('email')
                    ->label(__('users.labels.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Select::make('role')
                    ->label(__('users.labels.role'))
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'tenant' => 'Tenant',
                    ])
                    ->required(),
                
                Forms\Components\Toggle::make('is_active')
                    ->label(__('organizations.relations.users.active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'tenant' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('organizations.relations.users.active')),
                
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('users.labels.created_at', [], false) ?? __('app.common.na'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('users.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label(__('users.labels.role'))
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'tenant' => 'Tenant',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('organizations.relations.users.active')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('organizations.relations.users.empty_heading'))
            ->emptyStateDescription(__('organizations.relations.users.empty_description'))
            ->emptyStateIcon('heroicon-o-users');
    }
}
