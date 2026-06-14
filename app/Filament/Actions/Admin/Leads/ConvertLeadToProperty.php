<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Enums\ListingLeadStatus;
use App\Enums\PropertyType;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Building;
use App\Models\ListingLead;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConvertLeadToProperty
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $actor, ListingLead $lead, array $attributes): Property
    {
        Gate::forUser($actor)->authorize('convert', $lead);

        if ($lead->isConverted()) {
            throw ValidationException::withMessages([
                'lead' => __('admin.leads.validation.already_converted'),
            ]);
        }

        $data = Validator::make($attributes, [
            'building_id' => ['required', 'integer', Rule::exists('buildings', 'id')],
            'name' => ['nullable', 'string', 'max:255'],
            'unit_number' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(PropertyType::values())],
            'floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        $building = Building::query()
            ->select(['id', 'organization_id'])
            ->whereKey((int) $data['building_id'])
            ->firstOrFail();

        if ($building->organization_id !== $lead->organization_id) {
            throw ValidationException::withMessages([
                'building_id' => __('admin.leads.validation.building_organization_mismatch'),
            ]);
        }

        $property = Property::query()->create([
            'organization_id' => $lead->organization_id,
            'building_id' => $building->id,
            'name' => $data['name'] ?? $lead->listing_title ?? $lead->property_address ?? __('admin.leads.labels.converted_property'),
            'unit_number' => $data['unit_number'] ?? ($lead->external_id ?: 'lead-'.$lead->id),
            'type' => $data['type'] ?? $this->typeFromLead($lead),
            'floor_area_sqm' => $data['floor_area_sqm'] ?? $lead->area,
        ]);

        $lead->forceFill([
            'status' => ListingLeadStatus::CONVERTED,
            'converted_property_id' => $property->id,
            'converted_at' => now(),
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::CREATED,
            $property,
            [
                'context' => [
                    'mutation' => 'lead.converted',
                    'lead_id' => $lead->id,
                ],
            ],
            (int) $actor->id,
            'Lead converted to property',
        );

        return $property->refresh();
    }

    private function typeFromLead(ListingLead $lead): string
    {
        $value = str((string) $lead->property_type)->lower()->snake()->toString();

        return in_array($value, PropertyType::values(), true)
            ? $value
            : PropertyType::OTHER->value;
    }
}
