<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 1;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Organization Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from name, but can be customized'),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('domain')
                            ->maxLength(255)
                            ->helperText('Custom domain for this organization (optional)'),
                    ])->columns(2),

                Forms\Components\Section::make('Subscription & Limits')
                    ->schema([
                        Forms\Components\Select::make('plan')
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
                                    'basic' => ['properties' => 100, 'users' => 10],
                                    'professional' => ['properties' => 500, 'users' => 50],
                                    'enterprise' => ['properties' => 9999, 'users' => 999],
                                ];
                                $set('max_properties', $limits[$state]['properties']);
                                $set('max_users', $limits[$state]['users']);
                            }),
                        
                        Forms\Components\TextInput::make('max_properties')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->minValue(1),
                        
                        Forms\Components\TextInput::make('max_users')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1),
                        
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial End Date')
                            ->helperText('Leave empty if not on trial'),
                        
                        Forms\Components\DateTimePicker::make('subscription_ends_at')
                            ->label('Subscription End Date')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Regional Settings')
                    ->schema([
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'Europe/Vilnius' => 'Europe/Vilnius',
                                'Europe/London' => 'Europe/London',
                                'America/New_York' => 'America/New_York',
                                'UTC' => 'UTC',
                            ])
                            ->required()
                            ->default('Europe/Vilnius'),
                        
                        Forms\Components\Select::make('locale')
                            ->options([
                                'lt' => 'Lithuanian',
                                'en' => 'English',
                                'ru' => 'Russian',
                            ])
                            ->required()
                            ->default('lt'),
                        
                        Forms\Components\Select::make('currency')
                            ->options([
                                'EUR' => 'EUR (€)',
                                'USD' => 'USD ($)',
                            ])
                            ->required()
                            ->default('EUR'),
                    ])->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive organizations cannot access the system'),
                        
                        Forms\Components\DateTimePicker::make('suspended_at')
                            ->label('Suspended At')
                            ->disabled()
                            ->helperText('Set automatically when suspended'),
                        
                        Forms\Components\Textarea::make('suspension_reason')
                            ->maxLength(500)
                            ->rows(3)
                            ->disabled()
                            ->helperText('Reason for suspension'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'professional' => 'info',
                        'enterprise' => 'success',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('properties_count')
                    ->counts('properties')
                    ->label('Properties')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'basic' => 'Basic',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All organizations')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\Filter::make('subscription_expired')
                    ->query(fn (Builder $query): Builder => $query->where('subscription_ends_at', '<', now()))
                    ->label('Expired Subscriptions'),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('subscription_ends_at', '>=', now())
                        ->where('subscription_ends_at', '<=', now()->addDays(14)))
                    ->label('Expiring Soon (14 days)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Suspension Reason')
                            ->maxLength(500),
                    ])
                    ->action(function (Organization $record, array $data) {
                        $record->suspend($data['reason']);
                    })
                    ->visible(fn (Organization $record) => !$record->isSuspended()),
                
                Tables\Actions\Action::make('reactivate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Organization $record) => $record->reactivate())
                    ->visible(fn (Organization $record) => $record->isSuspended()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'view' => Pages\ViewOrganization::route('/{record}'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
}
