<?php

namespace App\Filament\Resources\PropertyAssignments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PropertyAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('property.name')->label(__('admin.properties.singular')),
                TextEntry::make('tenant_user_id')
                    ->numeric(),
                TextEntry::make('unit_area_sqm')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('assigned_at')
                    ->dateTime(),
                TextEntry::make('unassigned_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
