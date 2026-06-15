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
use App\Filament\Actions\Admin\BillingReview\VoidReading;
use App\Filament\Support\Admin\BillingReview\BillingReviewAccess;
use App\Filament\Support\Admin\BillingReview\BuildBillingReviewForPeriod;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class BillingInvoiceReview extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'billing-review-center/invoice-review';

    protected string $view = 'filament.pages.billing-invoice-review';

    #[Url(as: 'invoice')]
    public ?int $invoiceId = null;

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
     * @var array<int, string>
     */
    public array $voidReasons = [];

    public bool $acceptInvoiceWarnings = false;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        abort_if($this->invoiceId === null, 404);
    }

    public static function canAccess(): bool
    {
        return app(BillingReviewAccess::class)->canAccess(auth()->user());
    }

    public function getTitle(): string
    {
        return __('admin.billing_review.invoice_review.title');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('admin.billing_review.actions.back_to_center'))
                ->color('gray')
                ->url(route('filament.admin.pages.billing-review-center')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function review(): array
    {
        return app(BuildBillingReviewForPeriod::class)
            ->forInvoice($this->invoice())
            ->toArray();
    }

    public function approveReading(int $readingId, ApproveReading $approveReading): void
    {
        $approveReading->handle(
            $this->reading($readingId),
            $this->user(),
            (bool) ($this->confirmNegativeConsumption[$readingId] ?? false),
        );

        Notification::make()->title(__('admin.billing_review.messages.reading_approved'))->success()->send();
    }

    public function rejectReading(int $readingId, RejectReading $rejectReading): void
    {
        $rejectReading->handle(
            $this->reading($readingId),
            (string) ($this->rejectionComments[$readingId] ?? ''),
            $this->user(),
        );

        unset($this->rejectionComments[$readingId]);

        Notification::make()->title(__('admin.billing_review.messages.reading_rejected'))->success()->send();
    }

    public function correctReading(int $readingId, CorrectReading $correctReading): void
    {
        $correctReading->handle($this->reading($readingId), [
            'reading_value' => $this->correctionValues[$readingId] ?? null,
            'reason' => $this->correctionReasons[$readingId] ?? null,
            'confirm_negative_consumption' => (bool) ($this->confirmNegativeConsumption[$readingId] ?? false),
        ], $this->user());

        unset($this->correctionValues[$readingId], $this->correctionReasons[$readingId]);

        Notification::make()->title(__('admin.billing_review.messages.reading_corrected'))->success()->send();
    }

    public function requestResubmission(int $readingId, RequestReadingResubmission $requestReadingResubmission): void
    {
        $requestReadingResubmission->handle(
            $this->invoice(),
            $this->reading($readingId),
            (string) ($this->resubmissionComments[$readingId] ?? ''),
            $this->user(),
        );

        unset($this->resubmissionComments[$readingId]);

        Notification::make()->title(__('admin.billing_review.messages.resubmission_requested'))->success()->send();
    }

    public function voidReading(int $readingId, VoidReading $voidReading): void
    {
        $voidReading->handle(
            $this->reading($readingId),
            (string) ($this->voidReasons[$readingId] ?? ''),
            $this->user(),
        );

        unset($this->voidReasons[$readingId]);

        Notification::make()->title(__('admin.billing_review.messages.reading_voided'))->success()->send();
    }

    public function recalculateInvoice(RecalculateInvoice $recalculateInvoice): void
    {
        $recalculateInvoice->handle($this->invoice(), $this->user());

        Notification::make()->title(__('admin.billing_review.messages.invoice_recalculated'))->success()->send();
    }

    public function approveInvoice(ApproveInvoice $approveInvoice): void
    {
        $approveInvoice->handle($this->invoice(), $this->user(), acceptWarnings: $this->acceptInvoiceWarnings);

        $this->acceptInvoiceWarnings = false;

        Notification::make()->title(__('admin.billing_review.messages.invoice_approved'))->success()->send();
    }

    public function sendInvoice(SendInvoiceToTenant $sendInvoiceToTenant): void
    {
        $sendInvoiceToTenant->handle($this->invoice(), $this->user());

        Notification::make()->title(__('admin.billing_review.messages.invoice_sent'))->success()->send();
    }

    public function sendReminder(SendReadingReminder $sendReadingReminder): void
    {
        $sendReadingReminder->handle($this->invoice(), $this->user());

        Notification::make()->title(__('admin.billing_review.messages.reminder_sent'))->success()->send();
    }

    private function invoice(): Invoice
    {
        return Invoice::query()
            ->select(['id', 'organization_id', 'billing_period_id', 'property_id', 'tenant_user_id', 'invoice_number', 'billing_period_start', 'billing_period_end', 'status', 'currency', 'total_amount', 'due_date', 'items', 'approval_status', 'automation_level', 'approval_metadata', 'updated_at'])
            ->forOrganization($this->organization()->id)
            ->whereKey($this->invoiceId)
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
