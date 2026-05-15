<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use App\Filament\Support\Billing\InvoiceContentLocalizer;
use App\Models\InvoiceItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.id')->label(__('superadmin.relation_resources.invoice_items.fields.invoice')),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->lineItemDescription($record->description)),
                TextEntry::make('quantity')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.quantity'))
                    ->numeric(),
                TextEntry::make('unit')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->unit($record->unit))
                    ->placeholder('-'),
                TextEntry::make('unit_price')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit_price'))
                    ->money(),
                TextEntry::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->numeric(),
                TextEntry::make('meter_reading_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.meter_reading_snapshot'))
                    ->placeholder('-')
                    ->columnSpanFull(),
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
