<?php

namespace App\Filament\Resources\InvoiceEmailLogs\Tables;

use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceEmailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')->label(__('admin.invoices.singular'))
                    ->searchable(),
                TextColumn::make('organization.name')->label(__('superadmin.organizations.singular'))
                    ->searchable(),
                TextColumn::make('sentBy.name')
                    ->sortable(),
                TextColumn::make('recipient_email')
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')->label(__('superadmin.organizations.singular'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
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
