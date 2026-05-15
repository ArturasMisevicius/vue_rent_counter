<?php

namespace App\Filament\Resources\PropertyAssignments\Tables;

use App\Models\PropertyAssignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertyAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.organization'))
                    ->searchable(),
                TextColumn::make('property.name')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.property'))
                    ->state(fn (PropertyAssignment $record): string => $record->property?->displayName() ?? '—')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.tenant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_area_sqm')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.unit_area_sqm'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('assigned_at')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.assigned_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('unassigned_at')
                    ->label(__('superadmin.relation_resources.property_assignments.fields.unassigned_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
