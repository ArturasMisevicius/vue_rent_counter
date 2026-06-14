<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Schemas;

use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\ExtraChargeType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExtraChargeTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.extra_charge_types.sections.details'))
                    ->schema([
                        TextEntry::make('organization.name')
                            ->label(__('superadmin.organizations.singular')),
                        TextEntry::make('name')
                            ->label(__('admin.extra_charge_types.fields.name')),
                        TextEntry::make('type')
                            ->label(__('admin.extra_charge_types.fields.type'))
                            ->state(fn (ExtraChargeType $record): string => $record->typeLabel())
                            ->badge(),
                        TextEntry::make('default_amount')
                            ->label(__('admin.extra_charge_types.fields.default_amount'))
                            ->state(fn (ExtraChargeType $record): string => EuMoneyFormatter::format($record->default_amount, $record->currency)),
                        TextEntry::make('currency')
                            ->label(__('admin.extra_charge_types.fields.currency')),
                    ])
                    ->columns(2),
                Section::make(__('admin.extra_charge_types.sections.rules'))
                    ->schema([
                        IconEntry::make('is_recurring')
                            ->label(__('admin.extra_charge_types.fields.is_recurring'))
                            ->boolean(),
                        IconEntry::make('is_taxable')
                            ->label(__('admin.extra_charge_types.fields.is_taxable'))
                            ->boolean(),
                        IconEntry::make('tenant_visible_by_default')
                            ->label(__('admin.extra_charge_types.fields.tenant_visible_by_default'))
                            ->boolean(),
                        IconEntry::make('requires_comment')
                            ->label(__('admin.extra_charge_types.fields.requires_comment'))
                            ->boolean(),
                        IconEntry::make('requires_attachment')
                            ->label(__('admin.extra_charge_types.fields.requires_attachment'))
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->label(__('admin.extra_charge_types.fields.is_active'))
                            ->boolean(),
                    ])
                    ->columns(3),
            ]);
    }
}
