<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TagInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('color')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('type')
                    ->placeholder('-'),
                IconEntry::make('is_system')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
