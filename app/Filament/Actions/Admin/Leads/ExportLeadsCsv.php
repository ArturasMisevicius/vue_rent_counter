<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Leads;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ListingLead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ExportLeadsCsv
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function handle(User $actor, Organization $organization, array $filters = []): string
    {
        Gate::forUser($actor)->authorize('export', ListingLead::class);

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'status',
            'listing_title',
            'property_address',
            'city',
            'price',
            'currency',
            'owner_name',
            'owner_phone',
            'owner_email',
            'source_url',
            'last_contacted_at',
            'next_follow_up_at',
        ]);

        ListingLead::query()
            ->select([
                'id',
                'organization_id',
                'status',
                'listing_title',
                'property_address',
                'city',
                'price',
                'currency',
                'owner_name',
                'owner_phone',
                'owner_email',
                'source_url',
                'last_contacted_at',
                'next_follow_up_at',
                'assigned_to_user_id',
            ])
            ->forOrganization((int) $organization->id)
            ->when(
                $actor->isManager() && ! $actor->isAdmin(),
                fn (Builder $query): Builder => $query->assignedTo((int) $actor->id),
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query): Builder => $query->where('status', $filters['status']),
            )
            ->chunkById(500, function ($leads) use ($handle): void {
                foreach ($leads as $lead) {
                    fputcsv($handle, [
                        $lead->status?->value ?? $lead->status,
                        $lead->listing_title,
                        $lead->property_address,
                        $lead->city,
                        $lead->price,
                        $lead->currency,
                        $lead->owner_name,
                        $lead->owner_phone,
                        $lead->owner_email,
                        $lead->source_url,
                        $lead->last_contacted_at?->toDateTimeString(),
                        $lead->next_follow_up_at?->toDateTimeString(),
                    ]);
                }
            });

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $this->auditLogger->record(
            AuditLogAction::EXPORTED,
            $organization,
            [
                'context' => [
                    'mutation' => 'lead.exported',
                    'filters' => $filters,
                ],
            ],
            (int) $actor->id,
            'Lead CSV exported',
        );

        return $csv;
    }
}
