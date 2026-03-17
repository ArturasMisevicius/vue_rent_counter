<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\PropertyType;
use App\Models\Property;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.properties.columns.name')),
                        TextEntry::make('building.name')
                            ->label(__('admin.properties.columns.building')),
                        TextEntry::make('unit_number')
                            ->label(__('admin.properties.columns.unit_number')),
                        TextEntry::make('type')
                            ->label(__('admin.properties.columns.type'))
                            ->formatStateUsing(fn (PropertyType $state): string => __("admin.properties.types.{$state->value}")),
                        TextEntry::make('floor_area_sqm')
                            ->label(__('admin.properties.columns.floor_area_sqm'))
                            ->suffix(' sqm')
                            ->default(__('admin.properties.empty.none')),
                    ])
                    ->columns(2),
                Section::make(__('admin.properties.sections.current_occupancy'))
                    ->schema([
                        TextEntry::make('currentAssignment.tenant.name')
                            ->label(__('admin.properties.columns.tenant'))
                            ->default(__('admin.properties.empty.vacant')),
                        TextEntry::make('currentAssignment.assigned_at')
                            ->label(__('admin.properties.columns.assigned_since'))
                            ->dateTime()
                            ->default(__('admin.properties.empty.none')),
                    ])
                    ->columns(2),
                Section::make(__('admin.properties.sections.assignment_history'))
                    ->schema([
                        TextEntry::make('assignment_history')
                            ->label(__('admin.properties.sections.assignment_history'))
                            ->state(function (Property $record): array {
                                if (! $record->relationLoaded('assignments')) {
                                    $record->load('assignments.tenant');
                                }

                                $history = $record->assignments
                                    ->sortByDesc('assigned_at')
                                    ->map(function ($assignment): string {
                                        $assignedAt = $assignment->assigned_at instanceof Carbon
                                            ? $assignment->assigned_at->toDateString()
                                            : (string) $assignment->assigned_at;
                                        $unassignedAt = $assignment->unassigned_at instanceof Carbon
                                            ? $assignment->unassigned_at->toDateString()
                                            : null;
                                        $tenantName = $assignment->tenant?->name ?? __('admin.properties.empty.none');

                                        return $tenantName.' · '.$assignedAt.($unassignedAt ? ' → '.$unassignedAt : '');
                                    })
                                    ->values()
                                    ->all();

                                return $history === [] ? [__('admin.properties.empty.assignment_history')] : $history;
                            })
                            ->listWithLineBreaks(),
                    ]),
            ]);
    }
}
