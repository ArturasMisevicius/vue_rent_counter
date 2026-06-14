<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\LeadOutreachStatus;
use App\Enums\ListingLeadStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\LeadOutreachActivity;
use App\Models\ListingLead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ScheduleLeadFollowUp
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ListingLead $lead, string $followUpAt, ?string $note = null): LeadOutreachActivity
    {
        Gate::forUser($actor)->authorize('recordOutreach', $lead);

        $date = rescue(fn (): Carbon => Carbon::parse($followUpAt), report: false);

        if ($date === null) {
            throw ValidationException::withMessages([
                'next_follow_up_at' => __('admin.leads.validation.invalid_follow_up_date'),
            ]);
        }

        $lead->forceFill([
            'next_follow_up_at' => $date,
            'status' => ListingLeadStatus::FOLLOW_UP_NEEDED,
        ])->save();

        $activity = LeadOutreachActivity::query()->create([
            'organization_id' => $lead->organization_id,
            'listing_lead_id' => $lead->id,
            'lead_contact_id' => $lead->lead_contact_id,
            'user_id' => $actor->id,
            'channel' => LeadOutreachChannel::MANUAL,
            'direction' => LeadOutreachDirection::INTERNAL_NOTE,
            'message_summary' => $note ?: __('admin.leads.messages.follow_up_scheduled'),
            'status' => LeadOutreachStatus::SCHEDULED,
            'next_follow_up_at' => $date,
        ]);

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $activity,
            [
                'context' => [
                    'mutation' => 'lead.follow_up_scheduled',
                    'lead_id' => $lead->id,
                    'next_follow_up_at' => $date->toIso8601String(),
                ],
            ],
            (int) $actor->id,
            'Lead follow-up scheduled',
        );

        return $activity->refresh();
    }
}
