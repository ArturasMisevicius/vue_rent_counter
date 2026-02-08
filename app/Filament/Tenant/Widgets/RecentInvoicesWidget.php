<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Recent Invoices Widget for Tenant Dashboard
 * 
 * Shows the most recent invoices for the tenant's property
 * with quick actions to view or download.
 */
final class RecentInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = null;

    public static function canView(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT && $user->property_id;
    }

    public function getHeading(): string
    {
        return __('app.widgets.recent_invoices');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('number')
                    ->label(__('app.labels.invoice_number'))
                    ->searchable()
                    ->weight('medium'),
                
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::DRAFT => 'gray',
                        InvoiceStatus::FINALIZED => 'warning',
                        InvoiceStatus::PAID => 'success',
                        InvoiceStatus::OVERDUE => 'danger',
                        InvoiceStatus::CANCELLED => 'gray',
                    }),
                
                TextColumn::make('issue_date')
                    ->label(__('app.labels.issue_date'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('due_date')
                    ->label(__('app.labels.due_date'))
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date < now() && $record->status !== InvoiceStatus::PAID ? 'danger' : null),
                
                TextColumn::make('total_amount')
                    ->label(__('app.labels.total_amount'))
                    ->money('EUR')
                    ->sortable()
                    ->weight('medium'),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('app.actions.view'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (Invoice $record): string => 
                        route('filament.tenant.resources.invoices.view', $record)
                    )
                    ->color('gray'),
                
                Action::make('download')
                    ->label(__('app.actions.download_pdf'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('invoices.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->status !== InvoiceStatus::DRAFT)
                    ->color('primary'),
            ])
            ->defaultSort('issue_date', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('app.empty_states.no_invoices'))
            ->emptyStateDescription(__('app.empty_states.no_invoices_description'))
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        
        if (!$user || !$user->property_id) {
            return Invoice::query()->whereRaw('1 = 0'); // Empty query
        }
        
        return Invoice::query()
            ->where('property_id', $user->property_id)
            ->with(['property'])
            ->latest('issue_date')
            ->limit(10);
    }
}