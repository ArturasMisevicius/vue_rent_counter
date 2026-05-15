<?php

namespace App\Filament\Resources\Tags\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Tag;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TagInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.relation_resources.tags.fields.organization')),
                TextEntry::make('name')
                    ->label(__('superadmin.relation_resources.tags.fields.name'))
                    ->state(fn (Tag $record): string => $record->displayName()),
                TextEntry::make('color')
                    ->label(__('superadmin.relation_resources.tags.fields.color'))
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.tags.fields.description'))
                    ->state(fn (Tag $record): ?string => app(DatabaseContentLocalizer::class)->tagDescription($record->description))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('type')
                    ->label(__('superadmin.relation_resources.tags.fields.type'))
                    ->state(fn (Tag $record): string => $record->typeLabel())
                    ->placeholder('-'),
                IconEntry::make('is_system')
                    ->label(__('superadmin.relation_resources.tags.fields.is_system'))
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
