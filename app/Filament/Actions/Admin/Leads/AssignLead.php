<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\ListingLeadStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ListingLead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AssignLead
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ListingLead $lead, User $assignee): ListingLead
    {
        Gate::forUser($actor)->authorize('assign', $lead);

        if ($assignee->organization_id !== $lead->organization_id || ! $assignee->isAdminLike()) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => __('admin.leads.validation.invalid_assignee'),
            ]);
        }

        $before = $lead->only(['assigned_to_user_id', 'status']);

        $lead->forceFill([
            'assigned_to_user_id' => $assignee->id,
            'status' => $lead->status === ListingLeadStatus::NEW ? ListingLeadStatus::ASSIGNED : $lead->status,
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $lead,
            [
                'context' => ['mutation' => 'lead.assigned'],
                'before' => $before,
                'after' => $lead->only(['assigned_to_user_id', 'status']),
            ],
            (int) $actor->id,
            'Lead assigned',
        );

        return $lead->refresh();
    }
}
