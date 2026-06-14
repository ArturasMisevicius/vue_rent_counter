<?php

namespace App\Filament\Resources\InvoiceItems\Tables;

use App\Enums\InvoiceItemSourceType;
use App\Filament\Support\Billing\InvoiceContentLocalizer;
use App\Models\InvoiceItem;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InvoiceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAttentionQuery($query))
            ->columns([
                TextColumn::make('invoice.invoice_number')->label(__('admin.invoices.singular'))
                    ->searchable(),
                TextColumn::make('source_type')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.source_type'))
                    ->formatStateUsing(fn (mixed $state): string => self::sourceTypeLabel($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.title'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description'))
                    ->state(fn (InvoiceItem $record): string => app(InvoiceContentLocalizer::class)->lineItemDescription($record->description))
                    ->searchable(),
                TextColumn::make('description_for_tenant')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.description_for_tenant'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('subtotal')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.subtotal'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tax_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tax_amount'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_amount')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.discount_amount'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.total'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.currency'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('formula_label')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.formula_label'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('tenant_visible')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.tenant_visible'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('superadmin.relation_resources.invoice_items.fields.sort_order'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    private static function sourceTypeLabel(mixed $state): string
    {
        if ($state instanceof InvoiceItemSourceType) {
            return $state->label();
        }

        return InvoiceItemSourceType::tryFrom((string) $state)?->label() ?? '-';
    }

    private static function applyAttentionQuery(Builder $query): Builder
    {
        $attention = request()->query('attention');

        if (! is_string($attention) || $attention === '') {
            return $query;
        }

        return match ($attention) {
            'orphan_invoice_items' => $query->whereDoesntHave('invoice'),
            'duplicate_invoice_items' => $query->whereIn('id', self::duplicateInvoiceItemIdsForCurrentWorkspace()),
            default => $query,
        };
    }

    /**
     * @return list<int>
     */
    private static function duplicateInvoiceItemIdsForCurrentWorkspace(): array
    {
        $user = self::currentUser();

        return InvoiceItem::query()
            ->select(['id', 'invoice_id', 'source_type', 'source_id', 'service_configuration_id', 'utility_service_id'])
            ->when(
                ! ($user?->isSuperadmin() ?? false),
                fn (Builder $query): Builder => $user?->organization_id === null
                    ? $query->whereKey(-1)
                    : $query->whereHas('invoice', fn (Builder $invoiceQuery): Builder => $invoiceQuery->forOrganization((int) $user->organization_id)),
            )
            ->get()
            ->groupBy(fn (InvoiceItem $item): string => implode(':', [
                $item->invoice_id,
                $item->source_type?->value,
                $item->source_id,
                $item->service_configuration_id,
                $item->utility_service_id,
            ]))
            ->filter(fn ($group): bool => $group->count() > 1)
            ->flatMap(fn ($group) => $group->pluck('id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
