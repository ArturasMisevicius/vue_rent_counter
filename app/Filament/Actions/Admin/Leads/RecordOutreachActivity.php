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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordOutreachActivity
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, ListingLead $lead, array $attributes): LeadOutreachActivity
    {
        Gate::forUser($actor)->authorize('recordOutreach', $lead);

        $data = Validator::make($attributes, [
            'channel' => ['required', Rule::in(LeadOutreachChannel::values())],
            'direction' => ['required', Rule::in(LeadOutreachDirection::values())],
            'subject' => ['nullable', 'string', 'max:255'],
            'message_summary' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(LeadOutreachStatus::values())],
            'sent_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $direction = LeadOutreachDirection::from($data['direction']);
        $channel = LeadOutreachChannel::from($data['channel']);
        $contact = $lead->relationLoaded('contact') ? $lead->contact : $lead->contact()->first();

        if (
            $direction === LeadOutreachDirection::OUTBOUND
            && ($contact?->do_not_contact || $lead->status === ListingLeadStatus::DO_NOT_CONTACT)
            && ! $this->hasDoNotContactOverride($actor, $data['override_reason'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'direction' => __('admin.leads.validation.do_not_contact_blocked'),
            ]);
        }

        $status = isset($data['status'])
            ? LeadOutreachStatus::from($data['status'])
            : $this->defaultStatusFor($direction);

        $activity = LeadOutreachActivity::query()->create([
            'organization_id' => $lead->organization_id,
            'listing_lead_id' => $lead->id,
            'lead_contact_id' => $lead->lead_contact_id,
            'user_id' => $actor->id,
            'channel' => $channel,
            'direction' => $direction,
            'subject' => $data['subject'] ?? null,
            'message_summary' => $data['message_summary'],
            'status' => $status,
            'sent_at' => $data['sent_at'] ?? ($direction === LeadOutreachDirection::OUTBOUND ? now() : null),
            'received_at' => $data['received_at'] ?? ($direction === LeadOutreachDirection::INBOUND ? now() : null),
            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
            'completed_at' => in_array($status, [LeadOutreachStatus::COMPLETED, LeadOutreachStatus::SENT, LeadOutreachStatus::RECEIVED], true)
                ? now()
                : null,
            'internal_correction_reason' => $data['override_reason'] ?? null,
        ]);

        $this->updateLeadFromActivity($lead, $direction, $activity->next_follow_up_at);
        $this->updateContactFromActivity($lead, $direction);

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $activity,
            [
                'context' => [
                    'mutation' => 'lead.outreach_recorded',
                    'lead_id' => $lead->id,
                ],
            ],
            (int) $actor->id,
            'Lead outreach recorded',
        );

        return $activity->refresh();
    }

    private function hasDoNotContactOverride(User $actor, mixed $reason): bool
    {
        return ($actor->isAdmin() || $actor->isSuperadmin())
            && is_string($reason)
            && filled($reason);
    }

    private function defaultStatusFor(LeadOutreachDirection $direction): LeadOutreachStatus
    {
        return match ($direction) {
            LeadOutreachDirection::OUTBOUND => LeadOutreachStatus::SENT,
            LeadOutreachDirection::INBOUND => LeadOutreachStatus::RECEIVED,
            LeadOutreachDirection::INTERNAL_NOTE => LeadOutreachStatus::COMPLETED,
        };
    }

    private function updateLeadFromActivity(ListingLead $lead, LeadOutreachDirection $direction, ?Carbon $nextFollowUpAt): void
    {
        $attributes = [];

        if ($direction === LeadOutreachDirection::OUTBOUND) {
            $attributes['last_contacted_at'] = now();
            $attributes['status'] = $nextFollowUpAt !== null
                ? ListingLeadStatus::FOLLOW_UP_NEEDED
                : ListingLeadStatus::CONTACTED;
        }

        if ($direction === LeadOutreachDirection::INBOUND) {
            $attributes['last_contacted_at'] = now();
            $attributes['status'] = ListingLeadStatus::RESPONDED;
        }

        if ($nextFollowUpAt !== null) {
            $attributes['next_follow_up_at'] = $nextFollowUpAt;
        }

        if ($attributes !== []) {
            $lead->forceFill($attributes)->save();
        }
    }

    private function updateContactFromActivity(ListingLead $lead, LeadOutreachDirection $direction): void
    {
        if (! in_array($direction, [LeadOutreachDirection::OUTBOUND, LeadOutreachDirection::INBOUND], true)) {
            return;
        }

        $lead->contact()
            ->select(['id', 'last_contacted_at'])
            ->first()
            ?->forceFill(['last_contacted_at' => now()])
            ->save();
    }
}
