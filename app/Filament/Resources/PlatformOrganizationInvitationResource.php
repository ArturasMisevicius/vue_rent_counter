<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionPlanType;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\BulkCancelAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\BulkDeleteExpiredAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\BulkResendAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\CancelInvitationAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Actions\ResendInvitationAction;
use App\Filament\Resources\PlatformOrganizationInvitationResource\Pages;
use App\Models\PlatformOrganizationInvitation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class PlatformOrganizationInvitationResource extends Resource
{
    protected static ?string $model = PlatformOrganizationInvitation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Organization Invitations';

    protected static string|UnitEnum|null $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Invitation Details')
                    ->description('Invite a new organization to join the platform')
                    ->schema([
                        Forms\Components\TextInput::make('organization_name')
                            ->label('Organization Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The name of the organization being invited'),

                        Forms\Components\TextInput::make('admin_email')
                            ->label('Admin Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Email address for the organization administrator'),

                        Forms\Components\Select::make('plan_type')
                            ->label('Plan Type')
                            ->options([
                                SubscriptionPlanType::BASIC->value => 'Basic',
                                SubscriptionPlanType::PROFESSIONAL->value => 'Professional',
                                SubscriptionPlanType::ENTERPRISE->value => 'Enterprise',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Auto-populate limits based on plan type
                                $limits = match($state) {
                                    SubscriptionPlanType::BASIC->value => ['properties' => 10, 'users' => 5],
                                    SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 50, 'users' => 20],
                                    SubscriptionPlanType::ENTERPRISE->value => ['properties' => 200, 'users' => 100],
                                    default => ['properties' => 10, 'users' => 5],
                                };
                                $set('max_properties', $limits['properties']);
                                $set('max_users', $limits['users']);
                            })
                            ->helperText('Select the subscription plan for this organization'),

                        Forms\Components\TextInput::make('max_properties')
                            ->label('Max Properties')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(10)
                            ->helperText('Maximum number of properties allowed'),

                        Forms\Components\TextInput::make('max_users')
                            ->label('Max Users')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(5)
                            ->helperText('Maximum number of users allowed'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->required()
                            ->default(now()->addDays(7))
                            ->minDate(now())
                            ->helperText('Invitation will expire after this date (default: 7 days)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin_email')
                    ->label('Admin Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('plan_type')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SubscriptionPlanType::BASIC->value => 'gray',
                        SubscriptionPlanType::PROFESSIONAL->value => 'info',
                        SubscriptionPlanType::ENTERPRISE->value => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SubscriptionPlanType::BASIC->value => 'Basic',
                        SubscriptionPlanType::PROFESSIONAL->value => 'Professional',
                        SubscriptionPlanType::ENTERPRISE->value => 'Enterprise',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : ($record->expires_at->diffInDays(now()) <= 2 ? 'warning' : null)),

                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Accepted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not accepted'),

                Tables\Columns\TextColumn::make('inviter.name')
                    ->label('Invited By')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('Plan')
                    ->options([
                        SubscriptionPlanType::BASIC->value => 'Basic',
                        SubscriptionPlanType::PROFESSIONAL->value => 'Professional',
                        SubscriptionPlanType::ENTERPRISE->value => 'Enterprise',
                    ]),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->where('expires_at', '<=', now()->addDays(3))
                    ),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->where('expires_at', '<=', now())
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                ResendInvitationAction::make(),
                CancelInvitationAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkResendAction::make(),
                    BulkCancelAction::make(),
                    BulkDeleteExpiredAction::make(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isSuperadmin() ?? false),
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
            'index' => Pages\ListPlatformOrganizationInvitations::route('/'),
            'create' => Pages\CreatePlatformOrganizationInvitation::route('/create'),
            'view' => Pages\ViewPlatformOrganizationInvitation::route('/{record}'),
            'edit' => Pages\EditPlatformOrganizationInvitation::route('/{record}/edit'),
        ];
    }
}
