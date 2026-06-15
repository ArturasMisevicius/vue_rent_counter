<?php

declare(strict_types=1);

namespace App\Filament\Resources\BillingGenerationLogs\Schemas;

use App\Models\BillingGenerationLog;
use App\Models\BillingGenerationLogItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BillingGenerationLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.billing_generation.sections.summary'))
                    ->schema([
                        TextEntry::make('billingPeriod.name')
                            ->label(__('admin.billing_periods.singular')),
                        TextEntry::make('source')
                            ->label(__('admin.billing_generation.fields.source'))
                            ->badge(),
                        TextEntry::make('status')
                            ->label(__('admin.billing_generation.fields.status'))
                            ->badge(),
                        TextEntry::make('created_count')
                            ->label(__('admin.billing_generation.fields.created_count'))
                            ->numeric(),
                        TextEntry::make('skipped_count')
                            ->label(__('admin.billing_generation.fields.skipped_count'))
                            ->numeric(),
                        TextEntry::make('warning_count')
                            ->label(__('admin.billing_generation.fields.warning_count'))
                            ->numeric(),
                        TextEntry::make('error_count')
                            ->label(__('admin.billing_generation.fields.error_count'))
                            ->numeric(),
                        TextEntry::make('notified_tenants_count')
                            ->label(__('admin.billing_generation.fields.notified_tenants_count'))
                            ->numeric(),
                    ])
                    ->columns(4),
                Section::make(__('admin.billing_generation.sections.items'))
                    ->schema([
                        TextEntry::make('items')
                            ->label(__('admin.billing_generation.sections.items'))
                            ->state(fn (BillingGenerationLog $record): string => $record->items
                                ->map(fn (BillingGenerationLogItem $item): string => sprintf(
                                    '[%s] %s: %s',
                                    $item->level,
                                    $item->code,
                                    $item->message,
                                ))
                                ->implode("\n"))
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('admin.billing_generation.sections.schedule'))
                    ->schema([
                        TextEntry::make('billing_period_start')
                            ->label(__('admin.billing_generation.fields.billing_period_start'))
                            ->date(),
                        TextEntry::make('billing_period_end')
                            ->label(__('admin.billing_generation.fields.billing_period_end'))
                            ->date(),
                        TextEntry::make('invoice_generation_date')
                            ->label(__('admin.billing_generation.fields.invoice_generation_date'))
                            ->date(),
                        TextEntry::make('reading_submission_deadline')
                            ->label(__('admin.billing_generation.fields.reading_submission_deadline'))
                            ->date(),
                        TextEntry::make('payment_due_date')
                            ->label(__('admin.billing_generation.fields.payment_due_date'))
                            ->date(),
                    ])
                    ->columns(5),
            ]);
    }
}
