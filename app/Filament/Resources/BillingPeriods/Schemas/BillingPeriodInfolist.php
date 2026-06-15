<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingPeriods\Schemas;

use App\Models\BillingPeriod;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.billing_periods.sections.period'))
                    ->schema([
                        TextEntry::make('organization.name')
                            ->label(__('superadmin.organizations.singular')),
                        TextEntry::make('name')
                            ->label(__('admin.billing_periods.fields.name')),
                        TextEntry::make('starts_at')
                            ->label(__('admin.billing_periods.fields.starts_at'))
                            ->date(),
                        TextEntry::make('ends_at')
                            ->label(__('admin.billing_periods.fields.ends_at'))
                            ->date(),
                    ])
                    ->columns(2),
                Section::make(__('admin.billing_periods.sections.schedule'))
                    ->schema([
                        TextEntry::make('reading_submission_deadline')
                            ->label(__('admin.billing_periods.fields.reading_submission_deadline'))
                            ->date(),
                        TextEntry::make('invoice_generation_date')
                            ->label(__('admin.billing_periods.fields.invoice_generation_date'))
                            ->date(),
                        TextEntry::make('payment_due_date')
                            ->label(__('admin.billing_periods.fields.payment_due_date'))
                            ->date(),
                    ])
                    ->columns(3),
                Section::make(__('admin.billing_periods.sections.output'))
                    ->schema([
                        TextEntry::make('invoices_count')
                            ->label(__('admin.billing_periods.fields.invoices_count'))
                            ->state(fn (BillingPeriod $record): int => (int) ($record->invoices_count ?? $record->invoices()->count())),
                        TextEntry::make('reading_request_invoices_count')
                            ->label(__('admin.billing_periods.fields.reading_request_invoices_count'))
                            ->state(fn (BillingPeriod $record): int => (int) ($record->reading_request_invoices_count ?? $record->invoices()->where('automation_level', 'reading_request')->count())),
                    ])
                    ->columns(2),
            ]);
    }
}
