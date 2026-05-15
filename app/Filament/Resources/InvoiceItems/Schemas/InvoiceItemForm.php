<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.invoice'))
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('description')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description'))
                    ->required(),
                TextInput::make('quantity')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.quantity'))
                    ->required()
                    ->numeric(),
                TextInput::make('unit')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit')),
                TextInput::make('unit_price')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit_price'))
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->required()
                    ->numeric(),
                KeyValue::make('meter_reading_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.meter_reading_snapshot'))
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
