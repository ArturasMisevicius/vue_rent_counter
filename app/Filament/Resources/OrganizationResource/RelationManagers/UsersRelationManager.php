<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

    protected static string|BackedEnum|null $icon = 'heroicon-o-users';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('organizations.relations.users.title');
    }

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
                    ->options(\App\Enums\UserRole::labels())
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
            ->recordUrl(fn ($record): string => route('filament.admin.resources.users.edit', ['record' => $record]))
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
                    ->color(fn (string|BackedEnum $state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'tenant' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('organizations.relations.users.active')),
                
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('users.labels.last_login_at'))
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
                    ->options(\App\Enums\UserRole::labels()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('organizations.relations.users.active')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('organizations.relations.users.empty_heading'))
            ->emptyStateDescription(__('organizations.relations.users.empty_description'))
            ->emptyStateIcon('heroicon-o-users');
    }
}
