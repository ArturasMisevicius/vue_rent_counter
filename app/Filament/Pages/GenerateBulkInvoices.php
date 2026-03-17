<?php

namespace App\Filament\Pages;

use App\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Models\Organization;
use App\Support\Admin\Invoices\BulkInvoicePreviewBuilder;
use Filament\Pages\Page;

class GenerateBulkInvoices extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'generate-bulk-invoices';

    protected string $view = 'filament.pages.generate-bulk-invoices';

    /**
     * @var array{billing_period_start: string, billing_period_end: string, due_date: string}
     */
    public array $form = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $preview = null;

    public function mount(): void
    {
        $this->form = [
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isManager();
    }

    public function getTitle(): string
    {
        return __('admin.invoices.bulk.title');
    }

    public function previewInvoices(BulkInvoicePreviewBuilder $bulkInvoicePreviewBuilder): void
    {
        /** @var Organization $organization */
        $organization = auth()->user()->organization;

        $this->preview = $bulkInvoicePreviewBuilder->handle($organization, $this->form);
    }

    public function generateInvoices(GenerateBulkInvoicesAction $generateBulkInvoicesAction): void
    {
        /** @var Organization $organization */
        $organization = auth()->user()->organization;

        $this->preview = $generateBulkInvoicesAction->handle($organization, $this->form, auth()->user());
    }
}
