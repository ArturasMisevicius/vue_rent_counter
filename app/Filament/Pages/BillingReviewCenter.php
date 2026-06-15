<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Actions\Admin\BillingReview\ApproveInvoice;
use App\Filament\Actions\Admin\BillingReview\ApproveReading;
use App\Filament\Actions\Admin\BillingReview\CorrectReading;
use App\Filament\Actions\Admin\BillingReview\RecalculateInvoice;
use App\Filament\Actions\Admin\BillingReview\RejectReading;
use App\Filament\Actions\Admin\BillingReview\RequestReadingResubmission;
use App\Filament\Actions\Admin\BillingReview\SendInvoiceToTenant;
use App\Filament\Actions\Admin\BillingReview\SendReadingReminder;
use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Support\Admin\BillingReview\BillingReviewAccess;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class BillingReviewCenter extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing-review-center';

    protected string $view = 'filament.pages.billing-review-center';

    /**
     * @var array{billing_period_start: string, billing_period_end: string}
     */
    public array $period = [];

    #[Url]
    public ?string $attention = null;

    #[Url(as: 'billing_period_id')]
    public ?int $billingPeriodId = null;

    /**
     * @var array<int, string>
     */
    public array $rejectionComments = [];

    /**
     * @var array<int, string>
     */
    public array $resubmissionComments = [];

    /**
     * @var array<int, string>
     */
    public array $correctionValues = [];

    /**
     * @var array<int, string>
     */
    public array $correctionReasons = [];

    /**
     * @var array<int, bool>
     */
    public array $confirmNegativeConsumption = [];

    /**
     * @var array<int, bool>
     */
    public array $acceptInvoiceWarnings = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $billingPeriod = $this->billingPeriodId === null
            ? null
            : BillingPeriod::query()
                ->select(['id', 'organization_id', 'starts_at', 'ends_at'])
                ->forOrganization($this->organization()->id)
                ->find($this->billingPeriodId);

        $this->period = [
            'billing_period_start' => $billingPeriod?->starts_at?->toDateString() ?? now()->startOfMonth()->toDateString(),
            'billing_period_end' => $billingPeriod?->ends_at?->toDateString() ?? now()->endOfMonth()->toDateString(),
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
            ContextualHelpAction::make('invoices.review'),
            Action::make('invoices')
                ->label(__('admin.invoices.plural'))
                ->color('gray')
                ->url(route('filament.admin.resources.invoices.index')),
        ];
    }

    /**
     * @return array{summary: array<string, int>, invoices: array<int, array<string, mixed>>, pending_readings: array<int, array<string, mixed>>}
     */
    #[Computed]
    public function review(): array
    {
        $review = app(BuildBillingReviewForPeriod::class)->handle(
            $this->organization()->id,
            (string) $this->period['billing_period_start'],
            (string) $this->period['billing_period_end'],
        );

        $invoices = collect($review['invoices'])
            ->filter(fn ($invoice): bool => $this->matchesAttentionFilter($invoice))
            ->map(fn ($invoice): array => $invoice->toArray())
            ->values()
            ->all();

        return [
            'summary' => $review['summary']->toArray(),
            'invoices' => $invoices,
            'pending_readings' => $this->pendingReadings($invoices),
        ];
    }

    private function matchesAttentionFilter(object $invoice): bool
    {
        return match ($this->attention) {
            'waiting_readings', 'waiting_for_readings' => $invoice->missingReadings !== [],
            'submitted_readings' => $invoice->submittedReadingsCount > 0,
            'pending_readings' => $invoice->submittedReadingsCount > 0 || $invoice->missingReadings !== [],
            'waiting_confirmation' => $invoice->submittedReadingsCount > 0
                && in_array($invoice->approvalStatus, ['readings_submitted', 'ready_for_review'], true),
            'ready_for_review' => $invoice->canApprove,
            'configuration_errors' => $invoice->blockingErrors !== [],
            'overdue' => $invoice->isOverdue,
            null, '' => true,
            default => true,
        };
    }

    public function recalculateInvoice(int $invoiceId, RecalculateInvoice $recalculateInvoice): void
    {
        $recalculateInvoice->handle($this->invoice($invoiceId), $this->user());

        Notification::make()
            ->title(__('admin.billing_review.messages.invoice_recalculated'))
            ->success()
            ->send();
    }

    public function approveReading(int $readingId, ApproveReading $approveReading): void
    {
        $approveReading->handle(
            $this->reading($readingId),
            $this->user(),
            (bool) ($this->confirmNegativeConsumption[$readingId] ?? false),
        );

        Notification::make()
            ->title(__('admin.billing_review.messages.reading_approved'))
            ->success()
            ->send();
    }

    public function rejectReading(int $readingId, RejectReading $rejectReading): void
    {
        $rejectReading->handle(
            $this->reading($readingId),
            (string) ($this->rejectionComments[$readingId] ?? ''),
            $this->user(),
        );

        unset($this->rejectionComments[$readingId]);

        Notification::make()
            ->title(__('admin.billing_review.messages.reading_rejected'))
            ->success()
            ->send();
    }

    public function correctReading(int $readingId, CorrectReading $correctReading): void
    {
        $correctReading->handle($this->reading($readingId), [
            'reading_value' => $this->correctionValues[$readingId] ?? null,
            'reason' => $this->correctionReasons[$readingId] ?? null,
            'confirm_negative_consumption' => (bool) ($this->confirmNegativeConsumption[$readingId] ?? false),
        ], $this->user());

        unset($this->correctionValues[$readingId], $this->correctionReasons[$readingId]);

        Notification::make()
            ->title(__('admin.billing_review.messages.reading_corrected'))
            ->success()
            ->send();
    }

    public function requestResubmission(int $invoiceId, int $readingId, RequestReadingResubmission $requestReadingResubmission): void
    {
        $requestReadingResubmission->handle(
            $this->invoice($invoiceId),
            $this->reading($readingId),
            (string) ($this->resubmissionComments[$readingId] ?? ''),
            $this->user(),
        );

        unset($this->resubmissionComments[$readingId]);

        Notification::make()
            ->title(__('admin.billing_review.messages.resubmission_requested'))
            ->success()
            ->send();
    }

    public function approveInvoice(int $invoiceId, ApproveInvoice $approveInvoice): void
    {
        $approveInvoice->handle(
            $this->invoice($invoiceId),
            $this->user(),
            acceptWarnings: (bool) ($this->acceptInvoiceWarnings[$invoiceId] ?? false),
        );

        unset($this->acceptInvoiceWarnings[$invoiceId]);

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
            ->select(['id', 'organization_id', 'billing_period_id', 'property_id', 'tenant_user_id', 'invoice_number', 'billing_period_start', 'billing_period_end', 'status', 'currency', 'total_amount', 'due_date', 'items', 'approval_status', 'automation_level', 'approval_metadata', 'updated_at'])
            ->forOrganization($this->organization()->id)
            ->whereKey($invoiceId)
            ->firstOrFail();
    }

    private function reading(int $readingId): MeterReading
    {
        return MeterReading::query()
            ->select(['id', 'organization_id', 'billing_period_id', 'property_id', 'tenant_id', 'meter_id', 'submitted_by_user_id', 'reading_value', 'reading_date', 'previous_value', 'current_value', 'consumption', 'validation_status', 'status', 'submitted_at', 'approved_by_user_id', 'approved_at', 'rejected_by_user_id', 'rejected_at', 'rejection_reason', 'corrected_by_user_id', 'correction_reason', 'tenant_comment', 'voided_at', 'submission_method', 'invoice_id', 'notes', 'created_at', 'updated_at'])
            ->forOrganization($this->organization()->id)
            ->whereKey($readingId)
            ->firstOrFail();
    }

    /**
     * @param  array<int, array<string, mixed>>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function pendingReadings(array $invoices): array
    {
        return collect($invoices)
            ->flatMap(function (array $invoice): array {
                $submitted = collect($invoice['submitted_readings'] ?? [])
                    ->map(fn (array $reading): array => $this->pendingReadingRow($invoice, $reading, 'submitted'));
                $missing = collect($invoice['missing_readings'] ?? [])
                    ->map(fn (array $reading): array => $this->pendingReadingRow($invoice, $reading, 'missing'));

                return $submitted->merge($missing)->all();
            })
            ->filter(fn (array $row): bool => $row['row_type'] === 'missing'
                || $row['pending_review']
                || $row['negative_consumption']
                || $row['high_consumption']
                || $row['strange_reading']
                || $row['blocking_errors'] !== []
                || in_array($row['approval_status'], ['readings_submitted', 'ready_for_review'], true))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $invoice
     * @param  array<string, mixed>  $reading
     * @return array<string, mixed>
     */
    private function pendingReadingRow(array $invoice, array $reading, string $rowType): array
    {
        return [
            ...$reading,
            'row_type' => $rowType,
            'invoice_id' => $invoice['invoice_id'],
            'invoice_number' => $invoice['invoice_number'],
            'tenant_name' => $invoice['tenant_name'],
            'tenant_email' => $invoice['tenant_email'],
            'property_name' => $invoice['property_name'],
            'billing_period' => $invoice['billing_period'],
            'approval_status' => $invoice['approval_status'],
            'review_url' => $invoice['review_url'],
            'can_approve_invoice' => $invoice['can_approve'],
        ];
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
