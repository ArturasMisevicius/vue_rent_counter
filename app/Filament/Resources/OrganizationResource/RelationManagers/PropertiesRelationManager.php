<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Properties';

    protected static BackedEnum|string|null $icon = 'heroicon-o-home';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('building.name')
                    ->label('Building')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('property_type')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('area')
                    ->label('Area (m²)')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tenants_count')
                    ->counts('tenants')
                    ->label('Tenants')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('meters_count')
                    ->counts('meters')
                    ->label('Meters')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_type')
                    ->options([
                        'apartment' => 'Apartment',
                        'house' => 'House',
                        'commercial' => 'Commercial',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => route('filament.admin.resources.properties.view', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading('No properties yet')
            ->emptyStateDescription('Properties will appear here when created')
            ->emptyStateIcon('heroicon-o-home');
    }
}
