<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'organization_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the organization for this subscription'),
                        
                        Forms\Components\Select::make('plan_type')
                            ->options([
                                'basic' => 'Basic',
                                'professional' => 'Professional',
                                'enterprise' => 'Enterprise',
                            ])
                            ->required()
                            ->default('basic')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $limits = [
                                    'basic' => ['properties' => 100, 'tenants' => 50],
                                    'professional' => ['properties' => 500, 'tenants' => 250],
                                    'enterprise' => ['properties' => 9999, 'tenants' => 9999],
                                ];
                                $set('max_properties', $limits[$state]['properties']);
                                $set('max_tenants', $limits[$state]['tenants']);
                            }),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(3),

                Forms\Components\Section::make('Subscription Period')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->required()
                            ->after('starts_at')
                            ->default(now()->addYear()),
                    ])->columns(2),

                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_properties')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1)
                            ->helperText('Maximum number of properties allowed'),
                        
                        Forms\Components\TextInput::make('max_tenants')
                            ->numeric()
                            ->required()
                            ->default(50)
                            ->minValue(1)
                            ->helperText('Maximum number of tenants allowed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.organization_name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('plan_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'professional' => 'info',
                        'enterprise' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'suspended' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : ($record->daysUntilExpiry() <= 14 ? 'warning' : 'success')),
                
                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->state(fn (Subscription $record) => $record->daysUntilExpiry())
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state <= 14 ? 'warning' : 'success'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderBy('expires_at', $direction)),
                
                Tables\Columns\TextColumn::make('max_properties')
                    ->label('Properties Limit')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('max_tenants')
                    ->label('Tenants Limit')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        'basic' => 'Basic',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'active')
                        ->where('expires_at', '>=', now())
                        ->where('expires_at', '<=', now()->addDays(14)))
                    ->label('Expiring Soon (14 days)'),
                
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now()))
                    ->label('Expired'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('new_expires_at')
                            ->label('New Expiration Date')
                            ->required()
                            ->after('today')
                            ->default(now()->addYear()),
                    ])
                    ->action(function (Subscription $record, array $data) {
                        $record->update([
                            'expires_at' => $data['new_expires_at'],
                            'status' => 'active',
                        ]);
                    })
                    ->requiresConfirmation(),
                
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn (Subscription $record) => $record->status === 'active'),
                
                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update(['status' => 'active']))
                    ->visible(fn (Subscription $record) => $record->status !== 'active'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expires_at', 'asc');
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
