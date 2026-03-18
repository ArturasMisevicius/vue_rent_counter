<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Invoice;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.invoices');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAdminWorkspaceRelations()->latestBillingFirst())
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(__('admin.invoices.columns.invoice_number'))
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
                ViewAction::make()
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('billing_period_start', 'desc');
    }
}
