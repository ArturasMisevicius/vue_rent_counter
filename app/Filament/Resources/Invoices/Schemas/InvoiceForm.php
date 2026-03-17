<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\User;
use App\Support\Admin\OrganizationContext;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.invoices.sections.details'))
                    ->schema([
                        Select::make('property_id')
                            ->label(__('admin.invoices.fields.property'))
                            ->relationship(
                                name: 'property',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
                                    ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tenant_user_id')
                            ->label(__('admin.invoices.fields.tenant'))
                            ->options(fn (): array => User::query()
                                ->select(['id', 'organization_id', 'name', 'role'])
                                ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                                ->where('role', 'tenant')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('billing_period_start')
                            ->label(__('admin.invoices.fields.billing_period_start'))
                            ->required(),
                        DatePicker::make('billing_period_end')
                            ->label(__('admin.invoices.fields.billing_period_end'))
                            ->required(),
                        DatePicker::make('due_date')
                            ->label(__('admin.invoices.fields.due_date')),
                        Select::make('status')
                            ->label(__('admin.invoices.fields.status'))
                            ->options([
                                InvoiceStatus::DRAFT->value => __('admin.invoices.statuses.draft'),
                                InvoiceStatus::FINALIZED->value => __('admin.invoices.statuses.finalized'),
                                InvoiceStatus::PARTIALLY_PAID->value => __('admin.invoices.statuses.partially_paid'),
                                InvoiceStatus::PAID->value => __('admin.invoices.statuses.paid'),
                                InvoiceStatus::OVERDUE->value => __('admin.invoices.statuses.overdue'),
                            ])
                            ->default(InvoiceStatus::DRAFT->value)
                            ->disabled(),
                        Textarea::make('notes')
                            ->label(__('admin.invoices.fields.notes'))
                            ->rows(4),
                    ])
                    ->columns(2),
            ]);
    }
}
