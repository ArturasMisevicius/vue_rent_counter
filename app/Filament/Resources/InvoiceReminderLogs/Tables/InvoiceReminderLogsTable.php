<?php

namespace App\Filament\Resources\InvoiceReminderLogs\Tables;

use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceReminderLogsTable
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
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_by'))
                    ->sortable(),
                TextColumn::make('recipient_email')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.recipient_email'))
                    ->searchable(),
                TextColumn::make('channel')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.channel'))
                    ->state(fn (InvoiceReminderLog $record): string => $record->channelLabel())
                    ->searchable(),
                TextColumn::make('sent_at')
                    ->label(__('superadmin.relation_resources.invoice_reminder_logs.fields.sent_at'))
                    ->dateTime()
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
