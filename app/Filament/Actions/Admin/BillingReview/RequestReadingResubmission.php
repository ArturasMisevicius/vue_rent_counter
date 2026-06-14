<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\BillingReview;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RequestReadingResubmission
{
    public function __construct(
        private RejectReading $rejectReading,
    ) {}

    public function handle(Invoice $invoice, MeterReading $reading, string $comment, ?User $actor = null): Invoice
    {
        $actor ??= auth()->user();
        $this->authorize($invoice, $reading, $actor);
        $this->guard($invoice, $comment);

        return DB::transaction(function () use ($invoice, $reading, $comment, $actor): Invoice {
            $this->rejectReading->handle($reading, $comment, $actor);

            $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];

            $invoice->forceFill([
                'approval_status' => 'waiting_for_readings',
                'approval_metadata' => [
                    ...$metadata,
                    'workflow' => $metadata['workflow'] ?? 'meter_reading_request',
                    'request_status' => 'needs_resubmission',
                    'resubmission_requested_at' => now()->toISOString(),
                    'resubmission_requested_by_user_id' => $actor?->id,
                    'resubmission_meter_reading_id' => (int) $reading->id,
                    'resubmission_comment' => $comment,
                    'linked_meters' => $this->markLinkedMetersForResubmission($metadata, $reading),
                    'required_inputs' => $this->markRequiredInputsForResubmission($metadata, $reading),
                ],
            ])->save();

            return $invoice->fresh(['tenant:id,organization_id,name,email']);
        });
    }

    private function guard(Invoice $invoice, string $comment): void
    {
        if ($invoice->status !== InvoiceStatus::DRAFT || $invoice->automation_level !== 'reading_request') {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_review.errors.resubmission_requires_reading_request'),
            ]);
        }

        if (blank($comment)) {
            throw ValidationException::withMessages([
                'tenant_visible_comment' => __('admin.billing_review.errors.resubmission_comment_required'),
            ]);
        }
    }

    private function authorize(Invoice $invoice, MeterReading $reading, ?User $actor): void
    {
        $sameWorkspace = (int) $invoice->organization_id === (int) $reading->organization_id
            && (int) $invoice->property_id === (int) $reading->property_id
            && (int) $invoice->tenant_user_id === (int) $reading->submitted_by_user_id;

        if (
            $sameWorkspace
            && $actor instanceof User
            && ! $actor->isTenant()
            && ($actor->isSuperadmin() || (int) $actor->organization_id === (int) $invoice->organization_id)
        ) {
            return;
        }

        throw new AuthorizationException;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return list<array<string, mixed>>
     */
    private function markLinkedMetersForResubmission(array $metadata, MeterReading $reading): array
    {
        return collect($metadata['linked_meters'] ?? [])
            ->filter(fn (mixed $meter): bool => is_array($meter))
            ->map(function (array $meter) use ($reading): array {
                if ((int) ($meter['id'] ?? 0) !== (int) $reading->meter_id) {
                    return $meter;
                }

                return [
                    ...$meter,
                    'status' => 'needs_resubmission',
                    'rejected_meter_reading_id' => (int) $reading->id,
                    'resubmission_requested_at' => now()->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return list<array<string, mixed>>
     */
    private function markRequiredInputsForResubmission(array $metadata, MeterReading $reading): array
    {
        return collect($metadata['required_inputs'] ?? [])
            ->filter(fn (mixed $input): bool => is_array($input))
            ->map(function (array $input) use ($reading): array {
                if ((int) ($input['meter_id'] ?? 0) !== (int) $reading->meter_id) {
                    return $input;
                }

                return [
                    ...$input,
                    'status' => 'needs_resubmission',
                    'rejected_meter_reading_id' => (int) $reading->id,
                    'resubmission_requested_at' => now()->toISOString(),
                ];
            })
            ->values()
            ->all();
    }
}
