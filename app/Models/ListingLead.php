<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListingLeadStatus;
use Database\Factories\ListingLeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingLead extends Model
{
    /** @use HasFactory<ListingLeadFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
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
        'created_at',
        'updated_at',
        'archived_at',
    ];

    protected $fillable = [
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
    ];

    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'rooms' => 'integer',
            'price' => 'decimal:2',
            'status' => ListingLeadStatus::class,
            'duplicate_reasons' => 'array',
            'raw_payload' => 'array',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'converted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ListingLeadStatus::activeValues());
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeForWorkspaceIndex(Builder $query, User $user, ?int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->when(
                ! $user->isSuperadmin(),
                fn (Builder $workspaceQuery): Builder => $organizationId === null
                    ? $workspaceQuery->whereKey(-1)
                    : $workspaceQuery->forOrganization($organizationId),
            )
            ->when(
                $user->isManager() && ! $user->isAdmin(),
                fn (Builder $managerQuery): Builder => $managerQuery->assignedTo((int) $user->id),
            )
            ->with([
                'organization:id,name',
                'source:id,organization_id,name,type',
                'contact:id,organization_id,name,phone,email,do_not_contact',
                'assignedTo:id,name,email',
                'convertedProperty:id,organization_id,name,unit_number',
            ])
            ->latestFirst();
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function displayTitle(): string
    {
        return filled($this->listing_title)
            ? (string) $this->listing_title
            : __('admin.leads.labels.untitled_listing');
    }

    public function isConverted(): bool
    {
        return $this->status === ListingLeadStatus::CONVERTED
            || $this->converted_property_id !== null;
    }

    public function canConvert(): bool
    {
        return ! $this->isConverted()
            && ! in_array($this->status, [
                ListingLeadStatus::ARCHIVED,
                ListingLeadStatus::DO_NOT_CONTACT,
                ListingLeadStatus::INVALID,
            ], true);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(LeadImportBatch::class, 'import_batch_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(LeadContact::class, 'lead_contact_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function convertedProperty(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'converted_property_id');
    }

    public function outreachActivities(): HasMany
    {
        return $this->hasMany(LeadOutreachActivity::class);
    }
}
