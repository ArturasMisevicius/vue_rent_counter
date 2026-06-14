<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
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

        $user = auth()->user();

        if ($user instanceof User && ($user->isAdmin() || $user->isManager()) && $user->organization_id !== null) {
            $actions[] = Action::make('generateBulk')
                ->label(__('admin.invoices.actions.generate_bulk'))
                ->url(route('filament.admin.pages.generate-bulk-invoices'))
                ->icon('heroicon-m-document-duplicate')
                ->button();

            if (InvoiceResource::canCreate()) {
                $actions[] = $this->openReadingCycleAction($user);
            }
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

    private function openReadingCycleAction(User $user): Action
    {
        return Action::make('openReadingCycle')
            ->label(__('admin.invoices.actions.open_reading_cycle'))
            ->icon('heroicon-m-bell-alert')
            ->color('gray')
            ->button()
            ->slideOver()
            ->authorize(fn (): bool => InvoiceResource::canCreate())
            ->modalHeading(__('admin.invoices.actions.open_reading_cycle_heading'))
            ->modalSubmitActionLabel(__('admin.invoices.actions.open_reading_cycle_submit'))
            ->schema([
                DatePicker::make('billing_period_start')
                    ->label(__('admin.invoices.fields.billing_period_start'))
                    ->required()
                    ->default(now()->subMonthNoOverflow()->startOfMonth()->toDateString()),
                DatePicker::make('billing_period_end')
                    ->label(__('admin.invoices.fields.billing_period_end'))
                    ->required()
                    ->default(now()->subMonthNoOverflow()->endOfMonth()->toDateString()),
                DatePicker::make('due_date')
                    ->label(__('admin.invoices.fields.due_date'))
                    ->required()
                    ->default(now()->subMonthNoOverflow()->endOfMonth()->addDays(14)->toDateString()),
            ])
            ->action(function (array $data, OpenReadingInvoiceCycleAction $openReadingInvoiceCycleAction) use ($user): void {
                $organization = $user->currentOrganization();

                abort_unless($organization instanceof Organization, 403);

                $result = $openReadingInvoiceCycleAction->handle($organization, $data, $user);

                Notification::make()
                    ->title(__('admin.invoices.messages.reading_cycle_opened', [
                        'created' => $result['created']->count(),
                        'skipped' => count($result['skipped']),
                        'notified' => $result['notified'],
                    ]))
                    ->success()
                    ->send();
            });
    }
}
