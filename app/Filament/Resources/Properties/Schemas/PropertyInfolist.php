<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Models\Property;
use App\Models\PropertyAssignment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.details'))
                    ->schema([
                        TextEntry::make('building.name')
                            ->label(__('admin.properties.fields.building')),
                        TextEntry::make('name')
                            ->label(__('admin.properties.fields.name')),
                        TextEntry::make('unit_number')
                            ->label(__('admin.properties.fields.unit_number')),
                        TextEntry::make('type')
                            ->label(__('admin.properties.fields.type'))
                            ->badge(),
                        TextEntry::make('floor_area_sqm')
                            ->label(__('admin.properties.fields.floor_area_sqm')),
                    ])
                    ->columns(2),
                Section::make(__('admin.properties.sections.current_occupancy'))
                    ->schema([
                        TextEntry::make('currentAssignment.tenant.name')
                            ->label(__('admin.properties.fields.current_tenant'))
                            ->default(__('admin.properties.empty.unassigned')),
                        TextEntry::make('currentAssignment.assigned_at')
                            ->label(__('admin.properties.fields.assigned_since'))
                            ->state(
                                fn (Property $record): string => $record->currentAssignment?->assigned_at?->format('Y-m-d H:i')
                                    ?? __('admin.properties.empty.unassigned'),
                            ),
                    ])
                    ->columns(2),
                Section::make(__('admin.properties.sections.assignment_history'))
                    ->schema([
                        TextEntry::make('assignment_history')
                            ->label(__('admin.properties.fields.assignment_history'))
                            ->state(function (Property $record): string {
                                $history = $record->assignments
                                    ->sortByDesc('assigned_at')
                                    ->map(function (PropertyAssignment $assignment): string {
                                        $tenantName = $assignment->tenant?->name ?? __('admin.properties.empty.unassigned');
                                        $assignedAt = $assignment->assigned_at?->format('Y-m-d');
                                        $unassignedAt = $assignment->unassigned_at?->format('Y-m-d');

                                        return collect([
                                            $tenantName,
                                            $assignedAt ? __('admin.properties.history.assigned_on', ['date' => $assignedAt]) : null,
                                            $unassignedAt ? __('admin.properties.history.unassigned_on', ['date' => $unassignedAt]) : null,
                                        ])->filter()->implode(' · ');
                                    })
                                    ->implode("\n");

                                return $history !== '' ? $history : __('admin.properties.empty.no_history');
                            }),
                    ]),
            ]);
    }
}
