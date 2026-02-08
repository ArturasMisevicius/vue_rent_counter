<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionRenewalResource\Pages;
use App\Models\SubscriptionRenewal;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class SubscriptionRenewalResource extends Resource
{
    protected static ?string $model = SubscriptionRenewal::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Renewal History';

    protected static ?string $modelLabel = 'Subscription Renewal';

    protected static ?string $pluralModelLabel = 'Subscription Renewals';

    protected static string|UnitEnum|null $navigationGroup = 'System Management';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->user->name}")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Leave empty for automatic renewals'),

                Forms\Components\Select::make('method')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automatic',
                    ])
                    ->required()
                    ->default('manual'),

                Forms\Components\Select::make('period')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annually' => 'Annually',
                    ])
                    ->required()
                    ->default('annually'),

                Forms\Components\DateTimePicker::make('old_expires_at')
                    ->label('Previous Expiry Date')
                    ->required(),

                Forms\Components\DateTimePicker::make('new_expires_at')
                    ->label('New Expiry Date')
                    ->required(),

                Forms\Components\TextInput::make('duration_days')
                    ->label('Duration (Days)')
                    ->numeric()
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subscription.user.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subscription.id')
                    ->label('Subscription ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manual' => 'primary',
                        'automatic' => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('period')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('old_expires_at')
                    ->label('Previous Expiry')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('new_expires_at')
                    ->label('New Expiry')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Renewed By')
                    ->placeholder('System (Automatic)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Renewed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automatic',
                    ]),

                Tables\Filters\SelectFilter::make('period')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'annually' => 'Annually',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Renewed From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Renewed Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('subscription.user_id')
                    ->label('Organization')
                    ->relationship('subscription.user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListSubscriptionRenewals::route('/'),
            'create' => Pages\CreateSubscriptionRenewal::route('/create'),
            'view' => Pages\ViewSubscriptionRenewal::route('/{record}'),
            'edit' => Pages\EditSubscriptionRenewal::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'superadmin';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === 'superadmin';
    }
}
