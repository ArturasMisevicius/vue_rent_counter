<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('admin.invoices.statuses.'.($state->value ?? $state))),
                TextColumn::make('total_amount')
                    ->label(__('admin.invoices.columns.total_amount'))
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label(__('admin.invoices.columns.amount_paid'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.invoices.fields.status'))
                    ->options([
                        'draft' => __('admin.invoices.statuses.draft'),
                        'finalized' => __('admin.invoices.statuses.finalized'),
                        'partially_paid' => __('admin.invoices.statuses.partially_paid'),
                        'paid' => __('admin.invoices.statuses.paid'),
                        'overdue' => __('admin.invoices.statuses.overdue'),
                    ]),
                SelectFilter::make('property_id')
                    ->relationship('property', 'name')
                    ->label(__('admin.invoices.fields.property')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
