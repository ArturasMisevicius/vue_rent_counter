<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Enums\ExtraChargeStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ExtraCharge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectExtraChargeAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ExtraCharge $charge, ?string $internalNote = null): ExtraCharge
    {
        if ($charge->status === ExtraChargeStatus::INCLUDED_IN_INVOICE) {
            throw ValidationException::withMessages([
                'status' => __('admin.extra_charges.messages.included_charge_cannot_be_rejected'),
            ]);
        }

        return DB::transaction(function () use ($actor, $charge, $internalNote): ExtraCharge {
            $before = $charge->getAttributes();

            $charge->update([
                'status' => ExtraChargeStatus::REJECTED,
                'internal_note' => filled($internalNote) ? $internalNote : $charge->internal_note,
            ]);

            $fresh = $charge->fresh(['tenant', 'type']);

            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $fresh,
                [
                    'context' => ['mutation' => 'extra_charge.rejected'],
                    'before' => $before,
                    'after' => $fresh->getAttributes(),
                ],
                $actor->id,
                'Extra charge rejected',
            );

            return $fresh;
        });
    }
}
