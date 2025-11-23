<?php

namespace App\Filament\Resources;

use App\Enums\ServiceType;
use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Providers';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 2;

    // Integrate ProviderPolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Provider::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Provider::class);
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
                Forms\Components\Section::make('Provider Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Provider Name')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Provider name is required',
                            ]),
                        
                        Forms\Components\Select::make('service_type')
                            ->label('Service Type')
                            ->options(ServiceType::class)
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => 'Service type is required',
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\KeyValue::make('contact_info')
                            ->label('Contact Details')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Contact Field')
                            ->reorderable(false)
                            ->helperText('Add contact information such as phone, email, address, website, etc.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Provider Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service Type')
                    ->badge()
                    ->color(fn (ServiceType $state): string => match ($state) {
                        ServiceType::ELECTRICITY => 'warning',
                        ServiceType::WATER => 'info',
                        ServiceType::HEATING => 'danger',
                    })
                    ->formatStateUsing(fn (?ServiceType $state): ?string => $state?->label())
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_info')
                    ->label('Contact Information')
                    ->formatStateUsing(function (?array $state): string {
                        if (empty($state)) {
                            return 'No contact info';
                        }
                        $items = [];
                        foreach ($state as $key => $value) {
                            $items[] = ucfirst($key) . ': ' . $value;
                        }
                        return implode(' | ', array_slice($items, 0, 2)) . (count($items) > 2 ? '...' : '');
                    })
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('tariffs_count')
                    ->label('Tariff Count')
                    ->counts('tariffs')
                    ->sortable(),
                
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
            RelationManagers\TariffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
