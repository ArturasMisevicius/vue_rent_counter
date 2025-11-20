<?php

namespace App\Filament\Resources;

use App\Enums\MeterType;
use App\Filament\Resources\MeterResource\Pages;
use App\Filament\Resources\MeterResource\RelationManagers;
use App\Models\Meter;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule;

class MeterResource extends Resource
{
    protected static ?string $model = Meter::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Meters';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    // Integrate MeterPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Meter::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Meter::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Visible to all authenticated users (Requirements 9.1, 9.2, 9.3)
    // Tenants can view meters for their properties
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('property_id')
                    ->label('Property')
                    ->options(Property::all()->pluck('address', 'id'))
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => 'The property is required.',
                        'exists' => 'The selected property does not exist.',
                    ]),
                
                Forms\Components\Select::make('type')
                    ->label('Meter Type')
                    ->options([
                        MeterType::ELECTRICITY->value => 'Electricity',
                        MeterType::WATER_COLD->value => 'Cold Water',
                        MeterType::WATER_HOT->value => 'Hot Water',
                        MeterType::HEATING->value => 'Heating',
                    ])
                    ->required()
                    ->native(false)
                    ->validationMessages([
                        'required' => 'The meter type is required.',
                    ]),
                
                Forms\Components\TextInput::make('serial_number')
                    ->label('Serial Number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'required' => 'The meter serial number is required.',
                        'unique' => 'This serial number is already registered.',
                    ]),
                
                Forms\Components\DatePicker::make('installation_date')
                    ->label('Installation Date')
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->validationMessages([
                        'required' => 'The installation date is required.',
                        'before_or_equal' => 'The installation date cannot be in the future.',
                    ]),
                
                Forms\Components\Toggle::make('supports_zones')
                    ->label('Supports Time-of-Use Zones')
                    ->helperText('Enable for electricity meters with day/night rate capability')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('property.address')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Meter Type')
                    ->badge()
                    ->color(fn (MeterType $state): string => match ($state) {
                        MeterType::ELECTRICITY => 'warning',
                        MeterType::WATER_COLD => 'info',
                        MeterType::WATER_HOT => 'danger',
                        MeterType::HEATING => 'success',
                    })
                    ->formatStateUsing(fn (MeterType $state): string => match ($state) {
                        MeterType::ELECTRICITY => 'Electricity',
                        MeterType::WATER_COLD => 'Cold Water',
                        MeterType::WATER_HOT => 'Hot Water',
                        MeterType::HEATING => 'Heating',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('installation_date')
                    ->label('Installation Date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('supports_zones')
                    ->label('Zones')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Meter Type')
                    ->options([
                        MeterType::ELECTRICITY->value => 'Electricity',
                        MeterType::WATER_COLD->value => 'Cold Water',
                        MeterType::WATER_HOT->value => 'Hot Water',
                        MeterType::HEATING->value => 'Heating',
                    ])
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('supports_zones')
                    ->label('Supports Zones')
                    ->placeholder('All meters')
                    ->trueLabel('With zones')
                    ->falseLabel('Without zones')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('property.address', 'asc');
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
            'index' => Pages\ListMeters::route('/'),
            'create' => Pages\CreateMeter::route('/create'),
            'edit' => Pages\EditMeter::route('/{record}/edit'),
        ];
    }
}
