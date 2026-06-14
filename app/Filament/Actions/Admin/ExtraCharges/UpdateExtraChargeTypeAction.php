<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\ExtraCharges\ExtraChargeTypeRequest;
use App\Models\ExtraChargeType;
use App\Models\User;

class UpdateExtraChargeTypeAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ExtraChargeType $chargeType, array $data): ExtraChargeType
    {
        $before = $chargeType->getAttributes();
        $validated = (new ExtraChargeTypeRequest)
            ->forOrganization($chargeType->organization_id)
            ->ignore($chargeType->id)
            ->validatePayload($data, $actor);

        $chargeType->update($validated);
        $fresh = $chargeType->fresh();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $fresh,
            [
                'context' => ['mutation' => 'extra_charge_type.updated'],
                'before' => $before,
                'after' => $fresh->getAttributes(),
            ],
            $actor->id,
            'Extra charge type updated',
        );

        return $fresh;
    }
}
