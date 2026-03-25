<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('project.name')
                    ->label(__('superadmin.audit_logs.record_types.project')),
                TextEntry::make('title'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('priority'),
                TextEntry::make('created_by_user_id')
                    ->numeric(),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('estimated_hours')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('actual_hours')
                    ->numeric(),
                TextEntry::make('checklist')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
