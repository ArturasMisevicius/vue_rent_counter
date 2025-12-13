<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use App\Models\Provider;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static UnitEnum|string|null $navigationGroup = 'Configuration';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Section::make('Tariff Information')
                    ->schema([
                        Forms\Components\Select::make('provider_id')
                            ->label('Provider')
                            ->relationship('provider', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('utility_type')
                            ->options([
                                'electricity' => 'Electricity',
                                'gas' => 'Gas',
                                'water' => 'Water',
                                'heat' => 'Heat',
                                'sewage' => 'Sewage',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('rate_per_unit')
                            ->label('Rate per Unit')
                            ->numeric()
                            ->step(0.0001)
                            ->minValue(0)
                            ->required()
                            ->prefix('€'),
                            
                        Forms\Components\TextInput::make('currency')
                            ->default('EUR')
                            ->required()
                            ->maxLength(3),
                            
                        Forms\Components\TextInput::make('unit')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('kWh, m³, etc.'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->required()
                            ->default(now()),
                            
                        Forms\Components\DatePicker::make('valid_to')
                            ->after('valid_from'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('provider.name')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('utility_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'electricity' => 'warning',
                        'gas' => 'danger',
                        'water' => 'info',
                        'heat' => 'success',
                        'sewage' => 'gray',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\TextColumn::make('rate_per_unit')
                    ->label('Rate')
                    ->money('EUR')
                    ->sortable()
                    ->suffix(fn ($record) => " / {$record->unit}"),
                    
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('valid_to')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('utility_type')
                    ->options([
                        'electricity' => 'Electricity',
                        'gas' => 'Gas',
                        'water' => 'Water',
                        'heat' => 'Heat',
                        'sewage' => 'Sewage',
                    ]),
                    
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\TernaryFilter::make('is_active'),
                
                Tables\Filters\Filter::make('valid_period')
                    ->form([
                        Forms\Components\DatePicker::make('valid_from'),
                        Forms\Components\DatePicker::make('valid_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valid_from'],
                                fn (Builder $query, $date): Builder => $query->where('valid_from', '>=', $date),
                            )
                            ->when(
                                $data['valid_until'],
                                fn (Builder $query, $date): Builder => $query->where(function ($q) use ($date) {
                                    $q->where('valid_to', '<=', $date)->orWhereNull('valid_to');
                                }),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => 
                            $records->each(fn ($record) => $record->update(['is_active' => true]))
                        )
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => 
                            $records->each(fn ($record) => $record->update(['is_active' => false]))
                        )
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
