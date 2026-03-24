<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Property;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.properties.sections.summary'))
                    ->schema([
                        TextEntry::make('floor')
                            ->label(__('admin.properties.fields.floor'))
                            ->state(fn (Property $record): string => $record->floorDisplay()),
                        TextEntry::make('floor_area_sqm')
                            ->label(__('admin.properties.fields.area'))
                            ->state(fn (Property $record): string => $record->areaDisplay()),
                        TextEntry::make('currentAssignment.tenant.name')
                            ->label(__('admin.properties.fields.tenant'))
                            ->state(fn (Property $record): string => $record->currentAssignment?->tenant?->name ?? __('admin.properties.empty.vacant'))
                            ->url(fn (Property $record): ?string => $record->currentAssignment?->tenant !== null
                                ? TenantResource::getUrl('view', ['record' => $record->currentAssignment->tenant])
                                : null),
                        TextEntry::make('meters_count')
                            ->label(__('admin.properties.fields.meter_count')),
                    ])
                    ->columns(4),
                Section::make(__('admin.properties.sections.tenant_details'))
                    ->schema([
                        TextEntry::make('currentAssignment.tenant.name')
                            ->label(__('admin.tenants.fields.name'))
                            ->default(__('admin.properties.empty.no_tenant_assigned')),
                        TextEntry::make('currentAssignment.tenant.email')
                            ->label(__('admin.tenants.fields.email'))
                            ->default('—'),
                        TextEntry::make('currentAssignment.tenant.phone')
                            ->label(__('admin.tenants.fields.phone'))
                            ->default('—'),
                        TextEntry::make('currentAssignment.unit_area_sqm')
                            ->label(__('admin.tenants.fields.unit_area_sqm'))
                            ->state(fn (Property $record): string => $record->currentAssignment?->unit_area_sqm !== null
                                ? rtrim(rtrim(number_format((float) $record->currentAssignment->unit_area_sqm, 2, '.', ''), '0'), '.').' m²'
                                : '—'),
                        TextEntry::make('currentAssignment.tenant.status')
                            ->label(__('admin.tenants.fields.status'))
                            ->badge()
                            ->default('—'),
                        TextEntry::make('currentAssignment.assigned_at')
                            ->label(__('admin.properties.fields.date_assigned'))
                            ->state(fn (Property $record): string => $record->currentAssignment?->assigned_at?->format('F j, Y') ?? '—'),
                    ])
                    ->columns(2)
                    ->visible(fn (Property $record): bool => $record->currentAssignment !== null),
                Section::make(__('admin.properties.sections.tenant_details'))
                    ->schema([
                        TextEntry::make('tenant_empty')
                            ->hiddenLabel()
                            ->state(__('admin.properties.empty.no_tenant_assigned')),
                    ])
                    ->visible(fn (Property $record): bool => $record->currentAssignment === null),
            ]);
    }
}
