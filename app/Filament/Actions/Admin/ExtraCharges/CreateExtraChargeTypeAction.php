<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\ExtraCharges;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Http\Requests\Admin\ExtraCharges\ExtraChargeTypeRequest;
use App\Models\ExtraChargeType;
use App\Models\Organization;
use App\Models\User;

class CreateExtraChargeTypeAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, Organization $organization, array $data): ExtraChargeType
    {
        $validated = (new ExtraChargeTypeRequest)
            ->forOrganization($organization->id)
            ->validatePayload($data, $actor);

        $chargeType = ExtraChargeType::query()->create([
            'organization_id' => $organization->id,
            ...$validated,
        ]);

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $chargeType,
            [
                'context' => ['mutation' => 'extra_charge_type.created'],
                'after' => $chargeType->getAttributes(),
            ],
            $actor->id,
            'Extra charge type created',
        );

        return $chargeType->fresh();
    }
}
