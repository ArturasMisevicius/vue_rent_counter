<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\LeadImportBatchStatus;
use App\Enums\ListingLeadStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\LeadContact;
use App\Models\LeadImportBatch;
use App\Models\LeadSource;
use App\Models\ListingLead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ImportLeadCsv
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $preview
     */
    public function handle(
        User $actor,
        Organization $organization,
        LeadSource $source,
        array $preview,
        string $duplicateStrategy = 'flag',
    ): LeadImportBatch {
        Gate::forUser($actor)->authorize('create', LeadImportBatch::class);

        if (($preview['generated_at'] ?? null) === null || ! is_array($preview['rows'] ?? null)) {
            throw ValidationException::withMessages([
                'preview' => __('admin.leads.validation.preview_required'),
            ]);
        }

        if ($source->organization_id !== $organization->id) {
            throw ValidationException::withMessages([
                'lead_source_id' => __('admin.leads.validation.source_organization_mismatch'),
            ]);
        }

        $batch = LeadImportBatch::query()->create([
            'organization_id' => $organization->id,
            'lead_source_id' => $source->id,
            'filename' => (string) ($preview['filename'] ?? 'leads.csv'),
            'uploaded_by_user_id' => $actor->id,
            'rows_total' => (int) ($preview['rows_total'] ?? 0),
            'rows_imported' => 0,
            'rows_skipped' => 0,
            'rows_duplicates' => 0,
            'rows_failed' => 0,
            'status' => LeadImportBatchStatus::IMPORTED,
            'mapping_config' => $preview['mapping'] ?? [],
            'error_summary' => $preview['errors'] ?? [],
            'finished_at' => now(),
        ]);

        $counters = [
            'rows_imported' => 0,
            'rows_skipped' => 0,
            'rows_duplicates' => 0,
            'rows_failed' => 0,
        ];

        foreach ($preview['rows'] as $previewRow) {
            if (! is_array($previewRow)) {
                continue;
            }

            $errors = $previewRow['errors'] ?? [];
            $data = $previewRow['data'] ?? [];

            if (! is_array($data) || (is_array($errors) && $errors !== [])) {
                $counters['rows_failed']++;

                continue;
            }

            $duplicates = is_array($previewRow['duplicates'] ?? null) ? $previewRow['duplicates'] : [];

            if ($duplicates !== []) {
                $counters['rows_duplicates']++;
            }

            $existingLead = $this->findUpdatableExistingLead((int) $organization->id, $data);

            if ($existingLead instanceof ListingLead) {
                $this->updateExistingLead($existingLead, $source, $batch, $data, $previewRow);
                $counters['rows_imported']++;

                continue;
            }

            if ($duplicates !== [] && $duplicateStrategy === 'skip') {
                $counters['rows_skipped']++;

                continue;
            }

            $contact = $this->findOrCreateContact($organization, $data);
            $status = $this->statusForImportedLead($contact, $duplicates);

            ListingLead::query()->create([
                'organization_id' => $organization->id,
                'lead_source_id' => $source->id,
                'import_batch_id' => $batch->id,
                'lead_contact_id' => $contact?->id,
                'external_id' => $data['external_id'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'listing_title' => $data['listing_title'] ?? null,
                'property_address' => $data['property_address'] ?? null,
                'city' => $data['city'] ?? null,
                'district' => $data['district'] ?? null,
                'property_type' => $data['property_type'] ?? null,
                'area' => $data['area'] ?? null,
                'rooms' => $data['rooms'] ?? null,
                'floor' => $data['floor'] ?? null,
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? 'EUR',
                'owner_name' => $data['owner_name'] ?? null,
                'owner_phone' => $data['owner_phone'] ?? null,
                'owner_email' => $data['owner_email'] ?? null,
                'contact_raw' => $data['contact_raw'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $status,
                'duplicate_reasons' => $duplicates,
                'raw_payload' => $previewRow['raw'] ?? [],
            ]);

            $counters['rows_imported']++;
        }

        $batch->forceFill($counters)->save();
        $source->forceFill(['imported_at' => now()])->save();

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $batch,
            [
                'context' => [
                    'mutation' => 'lead.import_completed',
                    ...$counters,
                ],
            ],
            (int) $actor->id,
            'Lead CSV import completed',
        );

        return $batch->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findUpdatableExistingLead(int $organizationId, array $data): ?ListingLead
    {
        return ListingLead::query()
            ->select([
                'id',
                'organization_id',
                'lead_source_id',
                'import_batch_id',
                'lead_contact_id',
                'external_id',
                'source_url',
                'listing_title',
                'property_address',
                'city',
                'district',
                'property_type',
                'area',
                'rooms',
                'floor',
                'price',
                'currency',
                'owner_name',
                'owner_phone',
                'owner_email',
                'contact_raw',
                'description',
                'status',
                'duplicate_reasons',
                'raw_payload',
                'assigned_to_user_id',
                'last_contacted_at',
                'next_follow_up_at',
                'converted_property_id',
                'converted_at',
                'archived_at',
            ])
            ->forOrganization($organizationId)
            ->whereNotIn('status', [ListingLeadStatus::CONVERTED->value, ListingLeadStatus::ARCHIVED->value])
            ->where(function (Builder $query) use ($data): void {
                if (filled($data['source_url'] ?? null)) {
                    $query->orWhere('source_url', $data['source_url']);
                }

                if (filled($data['external_id'] ?? null)) {
                    $query->orWhere('external_id', $data['external_id']);
                }
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $previewRow
     */
    private function updateExistingLead(ListingLead $lead, LeadSource $source, LeadImportBatch $batch, array $data, array $previewRow): void
    {
        $lead->forceFill([
            'lead_source_id' => $source->id,
            'import_batch_id' => $batch->id,
            'listing_title' => $data['listing_title'] ?? $lead->listing_title,
            'property_address' => $data['property_address'] ?? $lead->property_address,
            'city' => $data['city'] ?? $lead->city,
            'district' => $data['district'] ?? $lead->district,
            'property_type' => $data['property_type'] ?? $lead->property_type,
            'area' => $data['area'] ?? $lead->area,
            'rooms' => $data['rooms'] ?? $lead->rooms,
            'floor' => $data['floor'] ?? $lead->floor,
            'price' => $data['price'] ?? $lead->price,
            'currency' => $data['currency'] ?? $lead->currency,
            'description' => $data['description'] ?? $lead->description,
            'duplicate_reasons' => $previewRow['duplicates'] ?? [],
            'raw_payload' => $previewRow['raw'] ?? [],
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findOrCreateContact(Organization $organization, array $data): ?LeadContact
    {
        $phone = $data['normalized_phone'] ?? null;
        $email = $data['normalized_email'] ?? null;

        if (! filled($phone) && ! filled($email)) {
            return null;
        }

        $contact = LeadContact::query()
            ->select([
                'id',
                'organization_id',
                'name',
                'phone',
                'email',
                'normalized_phone',
                'normalized_email',
                'preferred_channel',
                'do_not_contact',
                'do_not_contact_reason',
                'do_not_contact_at',
                'marked_do_not_contact_by_user_id',
            ])
            ->forOrganization((int) $organization->id)
            ->where(function (Builder $query) use ($phone, $email): void {
                if (filled($phone)) {
                    $query->orWhere('normalized_phone', $phone);
                }

                if (filled($email)) {
                    $query->orWhere('normalized_email', $email);
                }
            })
            ->first();

        if ($contact instanceof LeadContact) {
            $contact->forceFill([
                'name' => $contact->name ?: ($data['owner_name'] ?? null),
                'phone' => $contact->phone ?: ($data['owner_phone'] ?? null),
                'email' => $contact->email ?: ($data['owner_email'] ?? null),
                'normalized_phone' => $contact->normalized_phone ?: $phone,
                'normalized_email' => $contact->normalized_email ?: $email,
            ])->save();

            return $contact;
        }

        return LeadContact::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['owner_name'] ?? null,
            'phone' => $data['owner_phone'] ?? null,
            'email' => $data['owner_email'] ?? null,
            'normalized_phone' => $phone,
            'normalized_email' => $email,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $duplicates
     */
    private function statusForImportedLead(?LeadContact $contact, array $duplicates): ListingLeadStatus
    {
        if ($contact?->do_not_contact) {
            return ListingLeadStatus::DO_NOT_CONTACT;
        }

        return $duplicates === []
            ? ListingLeadStatus::NEW
            : ListingLeadStatus::DUPLICATE;
    }
}
