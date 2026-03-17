<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Models\Invoice;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tenants.sections.details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.tenants.fields.name')),
                        TextEntry::make('email')
                            ->label(__('admin.tenants.fields.email')),
                        TextEntry::make('locale')
                            ->label(__('admin.tenants.fields.locale')),
                        TextEntry::make('status')
                            ->label(__('admin.tenants.fields.status'))
                            ->badge(),
                    ])
                    ->columns(2),
                Section::make(__('admin.tenants.sections.current_property'))
                    ->schema([
                        TextEntry::make('currentPropertyAssignment.property.name')
                            ->label(__('admin.tenants.fields.current_property'))
                            ->default(__('admin.tenants.empty.property')),
                        TextEntry::make('currentPropertyAssignment.property.building.name')
                            ->label(__('admin.tenants.fields.building'))
                            ->default(__('admin.tenants.empty.property')),
                        TextEntry::make('currentPropertyAssignment.assigned_at')
                            ->label(__('admin.tenants.fields.assigned_since'))
                            ->dateTime()
                            ->placeholder(__('admin.tenants.empty.never')),
                    ])
                    ->columns(3),
                Section::make(__('admin.tenants.sections.invoice_history'))
                    ->schema([
                        TextEntry::make('invoice_history')
                            ->label(__('admin.tenants.fields.invoice_history'))
                            ->state(function (User $record): string {
                                $invoices = $record->relationLoaded('invoices')
                                    ? $record->invoices
                                    : Invoice::query()
                                        ->select(['id', 'organization_id', 'tenant_user_id', 'invoice_number', 'due_date'])
                                        ->forOrganization($record->organization_id)
                                        ->forTenant($record->id)
                                        ->latestBillingFirst()
                                        ->get();

                                $history = $invoices
                                    ->sortByDesc('due_date')
                                    ->map(fn (Invoice $invoice): string => $invoice->invoice_number)
                                    ->implode("\n");

                                return $history !== '' ? $history : __('admin.tenants.empty.invoices');
                            }),
                    ]),
            ]);
    }
}
