<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\ListingLeadStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ListingLead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ArchiveLead
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ListingLead $lead, ?string $reason = null): ListingLead
    {
        Gate::forUser($actor)->authorize('update', $lead);

        $lead->forceFill([
            'status' => ListingLeadStatus::ARCHIVED,
            'archived_at' => now(),
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::ARCHIVED,
            $lead,
            [
                'context' => [
                    'mutation' => 'lead.archived',
                    'reason' => $reason,
                ],
            ],
            (int) $actor->id,
            'Lead archived',
        );

        return $lead->refresh();
    }
}
