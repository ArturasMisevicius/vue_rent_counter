<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Schemas;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ExtraCharge;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExtraChargeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.extra_charges.sections.assignment'))
                    ->schema([
                        TextEntry::make('tenant.name')
                            ->label(__('admin.extra_charges.fields.tenant')),
                        TextEntry::make('property.name')
                            ->label(__('admin.extra_charges.fields.property'))
                            ->state(fn (ExtraCharge $record): string => $record->property?->displayName() ?? '—'),
                        TextEntry::make('billingPeriod.name')
                            ->label(__('admin.extra_charges.fields.billing_period'))
                            ->placeholder('—'),
                        TextEntry::make('invoice.invoice_number')
                            ->label(__('admin.extra_charges.fields.invoice'))
                            ->url(fn (ExtraCharge $record): ?string => $record->invoice_id !== null
                                ? InvoiceResource::getUrl('view', ['record' => $record->invoice_id])
                                : null)
                            ->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make(__('admin.extra_charges.sections.charge'))
                    ->schema([
                        TextEntry::make('type.name')
                            ->label(__('admin.extra_charges.fields.extra_charge_type')),
                        TextEntry::make('title')
                            ->label(__('admin.extra_charges.fields.title')),
                        TextEntry::make('description_for_tenant')
                            ->label(__('admin.extra_charges.fields.description_for_tenant'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('internal_note')
                            ->label(__('admin.extra_charges.fields.internal_note'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.extra_charges.sections.amounts'))
                    ->schema([
                        TextEntry::make('amount')
                            ->label(__('admin.extra_charges.fields.amount'))
                            ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->amount, $record->currency)),
                        TextEntry::make('quantity')
                            ->label(__('admin.extra_charges.fields.quantity')),
                        TextEntry::make('unit_price')
                            ->label(__('admin.extra_charges.fields.unit_price'))
                            ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->unit_price, $record->currency, 4)),
                        TextEntry::make('tax_amount')
                            ->label(__('admin.extra_charges.fields.tax_amount'))
                            ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->tax_amount, $record->currency)),
                        TextEntry::make('total_amount')
                            ->label(__('admin.extra_charges.fields.total_amount'))
                            ->state(fn (ExtraCharge $record): string => EuMoneyFormatter::format($record->total_amount, $record->currency)),
                    ])
                    ->columns(3),
                Section::make(__('admin.extra_charges.sections.workflow'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('admin.extra_charges.fields.status'))
                            ->state(fn (ExtraCharge $record): string => $record->statusLabel())
                            ->badge(),
                        IconEntry::make('is_recurring')
                            ->label(__('admin.extra_charges.fields.is_recurring'))
                            ->boolean(),
                        TextEntry::make('starts_at')
                            ->label(__('admin.extra_charges.fields.starts_at'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('ends_at')
                            ->label(__('admin.extra_charges.fields.ends_at'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('createdBy.name')
                            ->label(__('admin.extra_charges.fields.created_by')),
                        TextEntry::make('approvedBy.name')
                            ->label(__('admin.extra_charges.fields.approved_by'))
                            ->placeholder('—'),
                    ])
                    ->columns(3),
            ]);
    }
}
