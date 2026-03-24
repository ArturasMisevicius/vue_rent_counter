<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')
                    ->label('Invoice'),
                TextEntry::make('description'),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('unit')
                    ->placeholder('-'),
                TextEntry::make('unit_price')
                    ->money(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('meter_reading_snapshot')
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
