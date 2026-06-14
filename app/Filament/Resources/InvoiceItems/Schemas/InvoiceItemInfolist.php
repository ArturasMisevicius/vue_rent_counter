<?php

namespace App\Filament\Resources\InvoiceItems\Schemas;

use App\Enums\InvoiceItemSourceType;
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
                TextEntry::make('source_type')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.source_type'))
                    ->badge()
                    ->state(fn (InvoiceItem $record): string => self::sourceTypeLabel($record)),
                TextEntry::make('source_id')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.source_id'))
                    ->placeholder('-'),
                TextEntry::make('title')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.title'))
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->lineItemDescription($record->description)),
                TextEntry::make('description_for_tenant')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description_for_tenant'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('internal_note')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.internal_note'))
                    ->placeholder('-')
                    ->columnSpanFull(),
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
                TextEntry::make('subtotal')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.subtotal'))
                    ->numeric(),
                TextEntry::make('tax_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tax_amount'))
                    ->numeric(),
                TextEntry::make('discount_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.discount_amount'))
                    ->numeric(),
                TextEntry::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->numeric(),
                TextEntry::make('currency')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.currency'))
                    ->placeholder('-'),
                TextEntry::make('formula_label')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.formula_label'))
                    ->placeholder('-'),
                TextEntry::make('tenant_visible')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tenant_visible'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.projects.overview.yes') : __('admin.projects.overview.no')),
                TextEntry::make('sort_order')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.sort_order'))
                    ->numeric(),
                TextEntry::make('meter_reading_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.meter_reading_snapshot'))
                    ->state(fn (InvoiceItem $record): string => self::snapshot($record->meter_reading_snapshot))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('calculation_snapshot')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.calculation_snapshot'))
                    ->state(fn (InvoiceItem $record): string => self::snapshot($record->calculation_snapshot))
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

    private static function sourceTypeLabel(InvoiceItem $record): string
    {
        if ($record->source_type instanceof InvoiceItemSourceType) {
            return $record->source_type->label();
        }

        return InvoiceItemSourceType::tryFrom((string) $record->source_type)?->label() ?? '-';
    }

    private static function snapshot(mixed $snapshot): string
    {
        if (! is_array($snapshot) || $snapshot === []) {
            return '';
        }

        return (string) json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
