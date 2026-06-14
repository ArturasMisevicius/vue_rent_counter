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

class MergeDuplicateLeads
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ListingLead $sourceLead, ListingLead $targetLead, string $reason): ListingLead
    {
        Gate::forUser($actor)->authorize('update', $sourceLead);
        Gate::forUser($actor)->authorize('update', $targetLead);

        if ($sourceLead->organization_id !== $targetLead->organization_id || $sourceLead->is($targetLead)) {
            throw ValidationException::withMessages([
                'target_lead_id' => __('admin.leads.validation.invalid_merge_target'),
            ]);
        }

        $sourceLead->outreachActivities()
            ->select(['id', 'listing_lead_id'])
            ->update(['listing_lead_id' => $targetLead->id]);

        $sourceLead->forceFill([
            'status' => ListingLeadStatus::DUPLICATE,
            'duplicate_reasons' => [
                ['type' => 'merged', 'message' => $reason, 'lead_id' => $targetLead->id],
            ],
            'archived_at' => now(),
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $sourceLead,
            [
                'context' => [
                    'mutation' => 'lead.duplicates_merged',
                    'target_lead_id' => $targetLead->id,
                    'reason' => $reason,
                ],
            ],
            (int) $actor->id,
            'Duplicate lead merged',
        );

        return $targetLead->refresh();
    }
}
