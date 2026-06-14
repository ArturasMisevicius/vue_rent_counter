<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\ListingLeadStatus;
use App\Filament\Support\Admin\Leads\LeadDataNormalizer;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\LeadContact;
use App\Models\ListingLead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MarkLeadDoNotContact
{
    public function __construct(
        private readonly LeadDataNormalizer $normalizer,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(User $actor, ListingLead $lead, string $reason): ListingLead
    {
        Gate::forUser($actor)->authorize('recordOutreach', $lead);

        if (! filled($reason)) {
            throw ValidationException::withMessages([
                'do_not_contact_reason' => __('admin.leads.validation.do_not_contact_reason_required'),
            ]);
        }

        $contact = $lead->contact()->first() ?? $this->createContactFromLead($lead);

        $contact->forceFill([
            'do_not_contact' => true,
            'do_not_contact_reason' => $reason,
            'do_not_contact_at' => now(),
            'marked_do_not_contact_by_user_id' => $actor->id,
        ])->save();

        $lead->forceFill([
            'lead_contact_id' => $contact->id,
            'status' => ListingLeadStatus::DO_NOT_CONTACT,
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $lead,
            [
                'context' => [
                    'mutation' => 'lead.marked_do_not_contact',
                    'contact_id' => $contact->id,
                ],
            ],
            (int) $actor->id,
            'Lead marked do-not-contact',
        );

        return $lead->refresh();
    }

    private function createContactFromLead(ListingLead $lead): LeadContact
    {
        return LeadContact::query()->create([
            'organization_id' => $lead->organization_id,
            'name' => $lead->owner_name,
            'phone' => $lead->owner_phone,
            'email' => $lead->owner_email,
            'normalized_phone' => $this->normalizer->phone($lead->owner_phone),
            'normalized_email' => $this->normalizer->email($lead->owner_email),
        ]);
    }
}
