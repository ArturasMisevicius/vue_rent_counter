<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\ListingLeadStatus;
use App\Models\LeadContact;
use App\Models\ListingLead;
use Illuminate\Database\Eloquent\Builder;

class DetectLeadDuplicates
{
    /**
     * @param  array<string, mixed>  $row
     * @return list<array{type: string, message: string, lead_id?: int, contact_id?: int}>
     */
    public function handle(int $organizationId, array $row, ?int $ignoreLeadId = null): array
    {
        $duplicates = [];

        foreach (['source_url', 'external_id'] as $field) {
            if (! filled($row[$field] ?? null)) {
                continue;
            }

            $lead = $this->leadQuery($organizationId, $ignoreLeadId)
                ->where($field, $row[$field])
                ->first();

            if ($lead instanceof ListingLead) {
                $duplicates[] = [
                    'type' => $field,
                    'message' => __('admin.leads.duplicates.'.$field),
                    'lead_id' => (int) $lead->id,
                ];
            }
        }

        foreach (['normalized_phone', 'normalized_email'] as $field) {
            if (! filled($row[$field] ?? null)) {
                continue;
            }

            $contact = LeadContact::query()
                ->select(['id', 'organization_id', 'normalized_phone', 'normalized_email'])
                ->forOrganization($organizationId)
                ->where($field, $row[$field])
                ->first();

            if ($contact instanceof LeadContact) {
                $duplicates[] = [
                    'type' => $field,
                    'message' => __('admin.leads.duplicates.'.$field),
                    'contact_id' => (int) $contact->id,
                ];
            }
        }

        if (
            filled($row['property_address'] ?? null)
            && filled($row['listing_title'] ?? null)
            && filled($row['price'] ?? null)
        ) {
            $lead = $this->leadQuery($organizationId, $ignoreLeadId)
                ->where('property_address', $row['property_address'])
                ->where('listing_title', $row['listing_title'])
                ->where('price', $row['price'])
                ->first();

            if ($lead instanceof ListingLead) {
                $duplicates[] = [
                    'type' => 'address_title_price',
                    'message' => __('admin.leads.duplicates.address_title_price'),
                    'lead_id' => (int) $lead->id,
                ];
            }
        }

        return $duplicates;
    }

    private function leadQuery(int $organizationId, ?int $ignoreLeadId): Builder
    {
        return ListingLead::query()
            ->select([
                'id',
                'organization_id',
                'source_url',
                'external_id',
                'property_address',
                'listing_title',
                'price',
                'status',
            ])
            ->forOrganization($organizationId)
            ->where('status', '!=', ListingLeadStatus::ARCHIVED->value)
            ->when($ignoreLeadId !== null, fn (Builder $query): Builder => $query->whereKeyNot($ignoreLeadId));
    }
}
