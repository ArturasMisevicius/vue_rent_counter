<?php

namespace App\Filament\Resources\InvoiceItems\Tables;

use App\Filament\Support\Billing\InvoiceContentLocalizer;
use App\Models\InvoiceItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')->label(__('admin.invoices.singular'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->lineItemDescription($record->description))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.quantity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->unit($record->unit))
                    ->searchable(),
                TextColumn::make('unit_price')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.unit_price'))
                    ->money()
                    ->sortable(),
                TextColumn::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
