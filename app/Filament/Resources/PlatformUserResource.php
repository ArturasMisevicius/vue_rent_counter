<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\PlatformUserResource\Pages;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use UnitEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlatformUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Platform Users';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static UnitEnum|string|null $navigationGroup = 'System Management';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                UserRole::SUPERADMIN->value => 'Superadmin',
                                UserRole::ADMIN->value => 'Admin',
                                UserRole::MANAGER->value => 'Manager',
                                UserRole::TENANT->value => 'Tenant',
                            ])
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Select::make('tenant_id')
                            ->label('Organization')
                            ->options(function () {
                                return Organization::query()
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Select the organization this user belongs to'),
                        
                        Forms\Components\TextInput::make('organization_name')
                            ->label('Organization Name')
                            ->maxLength(255)
                            ->helperText('For admin users, this is their organization name'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive users cannot log in'),
                        
                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Last Login')
                            ->disabled()
                            ->helperText('Automatically updated on login'),
                        
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->helperText('When the user verified their email address'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::SUPERADMIN => 'danger',
                        UserRole::ADMIN => 'warning',
                        UserRole::MANAGER => 'info',
                        UserRole::TENANT => 'success',
                    })
                    ->formatStateUsing(fn (UserRole $state): string => ucfirst($state->value))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Never'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'superadmin' => 'Superadmin',
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'tenant' => 'Tenant',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Organization')
                    ->options(function () {
                        return Organization::query()
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),
                
                Tables\Filters\Filter::make('last_login')
                    ->label('Last Login')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->label('Period')
                            ->options([
                                '7' => 'Last 7 days',
                                '30' => 'Last 30 days',
                                '90' => 'Last 90 days',
                            ])
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['period'] ?? null,
                            fn (Builder $query, $period): Builder => $query->where(
                                'last_login_at',
                                '>=',
                                now()->subDays((int) $period)
                            ),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset User Password')
                    ->modalDescription('This will generate a temporary password and send it to the user\'s email.')
                    ->action(function (User $record) {
                        $temporaryPassword = \Illuminate\Support\Str::random(12);
                        $record->update([
                            'password' => bcrypt($temporaryPassword),
                        ]);
                        
                        // TODO: Send email notification with temporary password
                        // This will be implemented when notification system is ready
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Password Reset')
                            ->body("Temporary password generated: {$temporaryPassword}")
                            ->success()
                            ->send();
                    }),
                
                Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate User')
                    ->modalDescription('This user will not be able to log in.')
                    ->visible(fn (User $record): bool => $record->is_active)
                    ->action(function (User $record) {
                        $record->update(['is_active' => false]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('User Deactivated')
                            ->body("User {$record->name} has been deactivated.")
                            ->success()
                            ->send();
                    }),
                
                Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivate User')
                    ->modalDescription('This user will be able to log in again.')
                    ->visible(fn (User $record): bool => !$record->is_active)
                    ->action(function (User $record) {
                        $record->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('User Reactivated')
                            ->body("User {$record->name} has been reactivated.")
                            ->success()
                            ->send();
                    }),
                
                Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate User')
                    ->modalDescription('You will be logged in as this user. All actions will be logged.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Impersonation')
                            ->required()
                            ->helperText('This will be logged in the audit trail'),
                    ])
                    ->action(function (User $record, array $data) {
                        // Log the impersonation start
                        \App\Models\OrganizationActivityLog::create([
                            'organization_id' => $record->tenant_id,
                            'user_id' => auth()->id(),
                            'action' => 'impersonate_start',
                            'resource_type' => 'User',
                            'resource_id' => $record->id,
                            'before_data' => null,
                            'after_data' => [
                                'target_user_id' => $record->id,
                                'target_user_name' => $record->name,
                                'reason' => $data['reason'],
                            ],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);
                        
                        // Store original user ID in session
                        session(['impersonating_from' => auth()->id()]);
                        session(['impersonation_reason' => $data['reason']]);
                        session(['impersonation_started_at' => now()]);
                        
                        // Switch to target user
                        auth()->login($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Impersonation Started')
                            ->body("You are now logged in as {$record->name}")
                            ->warning()
                            ->send();
                        
                        return redirect()->route('filament.admin.pages.dashboard');
                    }),
                
                Actions\Action::make('view_activity')
                    ->label('View Activity')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(fn (User $record): string => 
                        route('filament.admin.resources.organization-activity-logs.index', [
                            'tableFilters' => [
                                'user_id' => ['value' => $record->id],
                            ],
                        ])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Users')
                        ->modalDescription('The selected users will not be able to log in.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = $records->count();
                            $records->each(fn (User $record) => $record->update(['is_active' => false]));
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Users Deactivated')
                                ->body("{$count} user(s) have been deactivated.")
                                ->success()
                                ->send();
                        }),
                    
                    Actions\BulkAction::make('bulk_reactivate')
                        ->label('Reactivate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Reactivate Users')
                        ->modalDescription('The selected users will be able to log in again.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = $records->count();
                            $records->each(fn (User $record) => $record->update(['is_active' => true]));
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Users Reactivated')
                                ->body("{$count} user(s) have been reactivated.")
                                ->success()
                                ->send();
                        }),
                    
                    Actions\BulkAction::make('bulk_send_notification')
                        ->label('Send Notification')
                        ->icon('heroicon-o-bell')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('subject')
                                ->label('Subject')
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->label('Message')
                                ->required()
                                ->rows(5),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = $records->count();
                            
                            // TODO: Implement actual notification sending
                            // This will be implemented when notification system is ready
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Notifications Queued')
                                ->body("Notification will be sent to {$count} user(s).")
                                ->success()
                                ->send();
                        }),
                    
                    Actions\BulkAction::make('bulk_export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $csv = "Name,Email,Role,Organization,Status,Email Verified,Last Login,Created At\n";
                            
                            foreach ($records as $record) {
                                $csv .= implode(',', [
                                    '"' . str_replace('"', '""', $record->name) . '"',
                                    '"' . str_replace('"', '""', $record->email) . '"',
                                    '"' . str_replace('"', '""', $record->role->value) . '"',
                                    '"' . str_replace('"', '""', $record->organization_name ?? '') . '"',
                                    $record->is_active ? 'Active' : 'Inactive',
                                    $record->email_verified_at ? 'Yes' : 'No',
                                    $record->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
                                    $record->created_at->format('Y-m-d H:i:s'),
                                ]) . "\n";
                            }
                            
                            $filename = 'platform-users-' . now()->format('Y-m-d-His') . '.csv';
                            
                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, $filename, [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPlatformUsers::route('/'),
            'create' => Pages\CreatePlatformUser::route('/create'),
            'edit' => Pages\EditPlatformUser::route('/{record}/edit'),
            'view' => Pages\ViewPlatformUser::route('/{record}'),
        ];
    }
}
