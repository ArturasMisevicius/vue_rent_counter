<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('admin.invoices.columns.invoice_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->label(__('admin.invoices.columns.tenant'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.invoices.columns.property'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.invoices.columns.status'))
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label(__('admin.invoices.columns.total_amount'))
                    ->formatStateUsing(fn ($state, Invoice $record): string => sprintf('%s %s', $record->currency, number_format((float) $state, 2)))
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('admin.invoices.columns.due_date'))
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->defaultSort('billing_period_start', 'desc');
    }
}
