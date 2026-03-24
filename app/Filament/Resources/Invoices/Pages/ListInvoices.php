<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(InvoiceResource::canViewAny(), 403);
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (InvoiceResource::canCreate()) {
            $actions[] = Action::make('create')
                ->label(__('admin.invoices.actions.new_invoice'))
                ->url(InvoiceResource::getUrl('create'))
                ->icon('heroicon-m-plus')
                ->button();
        }

        if (auth()->user()?->isAdminLike()) {
            $actions[] = Action::make('generateBulk')
                ->label(__('admin.invoices.actions.generate_bulk'))
                ->url(route('filament.admin.pages.generate-bulk-invoices'))
                ->icon('heroicon-m-document-duplicate')
                ->button();
        }

        return $actions;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('admin.invoices.tabs.all_invoices'))
                ->badge($this->tabCountQuery()->count()),
            'drafts' => Tab::make(__('admin.invoices.tabs.drafts'))
                ->badge((clone $this->tabCountQuery())->where('status', InvoiceStatus::DRAFT)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', InvoiceStatus::DRAFT)),
            'awaiting_payment' => Tab::make(__('admin.invoices.tabs.awaiting_payment'))
                ->badge((clone $this->tabCountQuery())->awaitingPayment()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->awaitingPayment()),
            'overdue' => Tab::make(__('admin.invoices.tabs.overdue'))
                ->badge((clone $this->tabCountQuery())->whereOverdueAsOf()->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereOverdueAsOf()),
        ];
    }

    /**
     * @return Builder<Invoice>
     */
    private function tabCountQuery(): Builder
    {
        $user = auth()->user();

        return Invoice::query()->forWorkspaceIndex(
            $user?->isSuperadmin() ?? false,
            app(OrganizationContext::class)->currentOrganizationId(),
        );
    }
}
