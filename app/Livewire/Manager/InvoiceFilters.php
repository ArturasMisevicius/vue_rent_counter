<?php

namespace App\Livewire\Manager;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Property;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class InvoiceFilters extends Component implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    public ?array $filters = [];
    
    public string $view = 'all'; // 'all', 'drafts', 'finalized'

    public function mount(string $view = 'all'): void
    {
        $this->view = $view;
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('status')
                    ->label(__('invoices.filters.status'))
                    ->options([
                        '' => __('invoices.filters.all_statuses'),
                        ...collect(InvoiceStatus::cases())
                            ->mapWithKeys(fn ($status) => [
                                $status->value => enum_label($status),
                            ])
                            ->toArray(),
                    ])
                    ->native(false)
                    ->placeholder(__('invoices.filters.all_statuses'))
                    ->live(onBlur: true),

                Select::make('property_id')
                    ->label(__('invoices.filters.property'))
                    ->options(fn () => Property::query()
                        ->orderBy('address')
                        ->pluck('address', 'id')
                        ->prepend(__('invoices.filters.all_properties'), '')
                    )
                    ->searchable()
                    ->native(false)
                    ->placeholder(__('invoices.filters.all_properties'))
                    ->live(onBlur: true),

                DatePicker::make('billing_period_from')
                    ->label(__('invoices.filters.billing_period_from'))
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->maxDate(now())
                    ->live(onBlur: true),

                DatePicker::make('billing_period_to')
                    ->label(__('invoices.filters.billing_period_to'))
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->maxDate(now())
                    ->live(onBlur: true),

                TextInput::make('min_amount')
                    ->label(__('invoices.filters.min_amount'))
                    ->numeric()
                    ->prefix(__('app.units.euro'))
                    ->minValue(0)
                    ->live(onBlur: true),

                TextInput::make('max_amount')
                    ->label(__('invoices.filters.max_amount'))
                    ->numeric()
                    ->prefix(__('app.units.euro'))
                    ->minValue(0)
                    ->live(onBlur: true),

                Select::make('sort')
                    ->label(__('invoices.filters.sort_by'))
                    ->options([
                        'created_at' => __('invoices.filters.sort.created_at'),
                        'billing_period_start' => __('invoices.filters.sort.billing_period'),
                        'total_amount' => __('invoices.filters.sort.amount'),
                        'due_date' => __('invoices.filters.sort.due_date'),
                    ])
                    ->default('created_at')
                    ->native(false)
                    ->live(onBlur: true),

                Select::make('direction')
                    ->label(__('invoices.filters.sort_direction'))
                    ->options([
                        'desc' => __('invoices.filters.sort.desc'),
                        'asc' => __('invoices.filters.sort.asc'),
                    ])
                    ->default('desc')
                    ->native(false)
                    ->live(onBlur: true),
            ])
            ->statePath('filters')
            ->columns(4);
    }

    public function getInvoicesProperty()
    {
        $query = Invoice::with(['tenant.property', 'items']);

        // Apply view filter
        if ($this->view === 'drafts') {
            $query->draft();
        } elseif ($this->view === 'finalized') {
            $query->finalized();
        }

        // Apply status filter
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Apply property filter
        if (!empty($this->filters['property_id'])) {
            $query->whereHas('tenant', function ($q) {
                $q->where('property_id', $this->filters['property_id']);
            });
        }

        // Apply billing period filters
        if (!empty($this->filters['billing_period_from'])) {
            $query->where('billing_period_start', '>=', $this->filters['billing_period_from']);
        }

        if (!empty($this->filters['billing_period_to'])) {
            $query->where('billing_period_end', '<=', $this->filters['billing_period_to']);
        }

        // Apply amount filters
        if (!empty($this->filters['min_amount'])) {
            $query->where('total_amount', '>=', $this->filters['min_amount']);
        }

        if (!empty($this->filters['max_amount'])) {
            $query->where('total_amount', '<=', $this->filters['max_amount']);
        }

        // Apply sorting
        $sortColumn = $this->filters['sort'] ?? 'created_at';
        $sortDirection = $this->filters['direction'] ?? 'desc';
        
        $allowedColumns = ['billing_period_start', 'billing_period_end', 'total_amount', 'created_at', 'due_date'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        return $query->paginate(20);
    }

    public function resetFilters(): void
    {
        $this->reset('filters');
        $this->form->fill();
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.manager.invoice-filters', [
            'invoices' => $this->invoices,
        ]);
    }
}
