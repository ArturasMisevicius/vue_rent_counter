<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use App\Enums\PropertyType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = null;

    protected static string|BackedEnum|null $icon = 'heroicon-o-home';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('organizations.relations.properties.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->recordUrl(fn ($record): string => route('filament.admin.resources.properties.edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('address')
                    ->label(__('properties.labels.address'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('building.name')
                    ->label(__('organizations.relations.properties.building'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('property_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('area')
                    ->label(__('organizations.relations.properties.area'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenants_count')
                    ->counts('tenants')
                    ->label(__('organizations.relations.properties.tenants'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('meters_count')
                    ->counts('meters')
                    ->label(__('organizations.relations.properties.meters'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('property_type')
                    ->options(PropertyType::labels())
                    ->native(false),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('filament-actions::edit.single.label'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record): string => route('filament.admin.resources.properties.edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
            ])
            ->emptyStateHeading(__('organizations.relations.properties.empty_heading'))
            ->emptyStateDescription(__('organizations.relations.properties.empty_description'))
            ->emptyStateIcon('heroicon-o-home');
    }
}
