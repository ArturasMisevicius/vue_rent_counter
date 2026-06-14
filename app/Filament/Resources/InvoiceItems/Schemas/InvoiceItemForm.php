<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use App\Enums\InvoiceItemSourceType;
use App\Filament\Support\Billing\InvoiceLineItemDescription;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                Select::make('source_type')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.source_type'))
                    ->options(InvoiceItemSourceType::options())
                    ->searchable()
                    ->required(),
                TextInput::make('source_id')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.source_id'))
                    ->numeric(),
                TextInput::make('title')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.title'))
                    ->maxLength(255),
                InvoiceLineItemDescription::textarea(
                    Textarea::make('description')
                        ->label(__('superadmin.relation_resources.invoice_items.fields.description')),
                ),
                Textarea::make('description_for_tenant')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description_for_tenant'))
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('internal_note')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.internal_note'))
                    ->rows(3)
                    ->columnSpanFull(),
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
                TextInput::make('subtotal')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.subtotal'))
                    ->numeric()
                    ->required(),
                TextInput::make('tax_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tax_amount'))
                    ->numeric()
                    ->required(),
                TextInput::make('discount_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.discount_amount'))
                    ->numeric()
                    ->required(),
                TextInput::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.currency'))
                    ->required()
                    ->maxLength(3),
                TextInput::make('formula_label')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.formula_label'))
                    ->maxLength(255),
                Toggle::make('tenant_visible')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tenant_visible'))
                    ->default(true),
                TextInput::make('sort_order')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.sort_order'))
                    ->numeric()
                    ->required(),
                KeyValue::make('meter_reading_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.meter_reading_snapshot'))
                    ->nullable()
                    ->columnSpanFull(),
                KeyValue::make('calculation_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.calculation_snapshot'))
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
