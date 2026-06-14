<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Admin\BillingReview\ApproveInvoice;
use App\Filament\Actions\Admin\BillingReview\RecalculateInvoice;
use App\Filament\Actions\Admin\BillingReview\SendInvoiceToTenant;
use App\Filament\Actions\Admin\BillingReview\SendReadingReminder;
use App\Filament\Support\Admin\BillingReview\BillingReviewAccess;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class BillingReviewCenter extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing-review-center';

    protected string $view = 'filament.pages.billing-review-center';

    /**
     * @var array{billing_period_start: string, billing_period_end: string}
     */
    public array $period = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->period = [
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
        ];
    }

    public static function canAccess(): bool
    {
        return app(BillingReviewAccess::class)->canAccess(auth()->user());
    }

    public function getTitle(): string
    {
        return __('admin.billing_review.title');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('invoices')
                ->label(__('admin.invoices.plural'))
                ->color('gray')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    /**
     * @return array{summary: array<string, int>, invoices: array<int, array<string, mixed>>}
     */
    #[Computed]
    public function review(): array
    {
        $review = app(BuildBillingReviewForPeriod::class)->handle(
            $this->organization()->id,
            (string) $this->period['billing_period_start'],
            (string) $this->period['billing_period_end'],
        );

        return [
            'summary' => $review['summary']->toArray(),
            'invoices' => collect($review['invoices'])
                ->map(fn ($invoice): array => $invoice->toArray())
                ->all(),
        ];
    }

    public function recalculateInvoice(int $invoiceId, RecalculateInvoice $recalculateInvoice): void
    {
        $recalculateInvoice->handle($this->invoice($invoiceId), $this->user());

        Notification::make()
            ->title(__('admin.billing_review.messages.invoice_recalculated'))
            ->success()
            ->send();
    }

    public function approveInvoice(int $invoiceId, ApproveInvoice $approveInvoice): void
    {
        $approveInvoice->handle($this->invoice($invoiceId), $this->user(), acceptWarnings: true);

        Notification::make()
            ->title(__('admin.billing_review.messages.invoice_approved'))
            ->success()
            ->send();
    }

    public function sendInvoice(int $invoiceId, SendInvoiceToTenant $sendInvoiceToTenant): void
    {
        $sent = $sendInvoiceToTenant->handle($this->invoice($invoiceId), $this->user());

        Notification::make()
            ->title($sent ? __('admin.billing_review.messages.invoice_sent') : __('admin.billing_review.messages.invoice_send_skipped'))
            ->success()
            ->send();
    }

    public function sendReminder(int $invoiceId, SendReadingReminder $sendReadingReminder): void
    {
        $sendReadingReminder->handle($this->invoice($invoiceId), $this->user());

        Notification::make()
            ->title(__('admin.billing_review.messages.reminder_sent'))
            ->success()
            ->send();
    }

    private function invoice(int $invoiceId): Invoice
    {
        return Invoice::query()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'invoice_number', 'billing_period_start', 'billing_period_end', 'status', 'currency', 'total_amount', 'due_date', 'items', 'approval_status', 'approval_metadata', 'updated_at'])
            ->forOrganization($this->organization()->id)
            ->whereKey($invoiceId)
            ->firstOrFail();
    }

    private function organization(): Organization
    {
        $organization = app(BillingReviewAccess::class)->organizationFor($this->user());

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
