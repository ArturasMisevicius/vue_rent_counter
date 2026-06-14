<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Enums\ExtraChargeStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ExtraCharge;
use App\Models\User;
use App\Notifications\Billing\ExtraChargeAddedToUpcomingInvoiceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveExtraChargeAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ExtraCharge $charge): ExtraCharge
    {
        if (! in_array($charge->status, [ExtraChargeStatus::DRAFT, ExtraChargeStatus::PENDING_REVIEW], true)) {
            throw ValidationException::withMessages([
                'status' => __('admin.extra_charges.messages.approval_state_required'),
            ]);
        }

        $fresh = DB::transaction(function () use ($actor, $charge): ExtraCharge {
            $before = $charge->getAttributes();

            $charge->update([
                'status' => ExtraChargeStatus::APPROVED,
                'approved_by_user_id' => $actor->id,
                'approved_at' => now(),
            ]);

            $fresh = $charge->fresh(['tenant', 'type']);

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $fresh,
                [
                    'context' => ['mutation' => 'extra_charge.approved'],
                    'before' => $before,
                    'after' => $fresh->getAttributes(),
                ],
                $actor->id,
                'Extra charge approved',
            );

            return $fresh;
        });

        if ($fresh->isTenantVisible() && $fresh->tenant instanceof User) {
            $fresh->tenant->notify(new ExtraChargeAddedToUpcomingInvoiceNotification($fresh));
        }

        return $fresh;
    }
}
