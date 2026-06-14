<?php

namespace App\Models;

use App\Enums\RentalContractStatus;
use App\Filament\Support\RentalContracts\RentalContractFile;
use Database\Factories\RentalContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RentalContract extends Model
{
    /** @use HasFactory<RentalContractFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'tenant_id',
        'property_id',
        'property_assignment_id',
        'contract_number',
        'status',
        'start_date',
        'end_date',
        'signed_date',
        'terminated_at',
        'termination_reason',
        'renewed_from_contract_id',
        'rent_amount',
        'deposit_amount',
        'currency',
        'tenant_visible',
        'internal_notes',
        'tenant_visible_notes',
        'created_by_user_id',
        'updated_by_user_id',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'property_id',
        'property_assignment_id',
        'contract_number',
        'status',
        'start_date',
        'end_date',
        'signed_date',
        'terminated_at',
        'termination_reason',
        'renewed_from_contract_id',
        'rent_amount',
        'deposit_amount',
        'currency',
        'tenant_visible',
        'internal_notes',
        'tenant_visible_notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => RentalContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_date' => 'date',
            'terminated_at' => 'datetime',
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'tenant_visible' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyAssignment(): BelongsTo
    {
        return $this->belongsTo(PropertyAssignment::class);
    }

    public function renewedFromContract(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_contract_id');
    }

    public function renewedContracts(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_contract_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function files(): MorphMany
    {
        return $this->attachments()
            ->forDocumentType(RentalContractFile::DOCUMENT_TYPE)
            ->latestFirst();
    }

    public function file(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('attachments.document_type', RentalContractFile::DOCUMENT_TYPE)
            ->ofMany(['id' => 'max'], fn (Builder $query): Builder => $query->where(
                'attachments.document_type',
                RentalContractFile::DOCUMENT_TYPE,
            ));
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RentalContractStatus::ACTIVE);
    }

    public function scopeVisibleToTenant(Builder $query): Builder
    {
        return $query->where('tenant_visible', true);
    }

    public function scopeExpiredUnmarked(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereDate('end_date', '<', today());
    }

    public function scopeExpiringOn(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query
            ->active()
            ->whereDate('end_date', $date);
    }

    public function scopeWithContractSummary(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->with([
                'tenant:id,organization_id,name,email,role,status,tenant_status,portal_access_enabled',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name,address_line_1,city',
                'propertyAssignment:id,organization_id,property_id,tenant_user_id,assigned_at,unassigned_at',
                'file:attachments.id,attachments.organization_id,attachments.attachable_type,attachments.attachable_id,attachments.uploaded_by_user_id,attachments.filename,attachments.original_filename,attachments.mime_type,attachments.size,attachments.disk,attachments.path,attachments.document_type,attachments.created_at',
            ]);
    }

    public function isActive(): bool
    {
        return $this->status === RentalContractStatus::ACTIVE;
    }

    public function canBeTerminated(): bool
    {
        return in_array($this->status, [
            RentalContractStatus::DRAFT,
            RentalContractStatus::ACTIVE,
        ], true);
    }

    public function canBeRenewed(): bool
    {
        return in_array($this->status, [
            RentalContractStatus::ACTIVE,
            RentalContractStatus::EXPIRED,
        ], true);
    }
}
