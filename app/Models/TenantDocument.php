<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use Database\Factories\TenantDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantDocument extends Model
{
    /** @use HasFactory<TenantDocumentFactory> */
    use HasFactory;

    use SoftDeletes;

    private const TENANT_PORTAL_COLUMNS = [
        'id',
        'organization_id',
        'tenant_id',
        'property_id',
        'related_type',
        'related_id',
        'document_type',
        'title',
        'description_for_tenant',
        'file_path',
        'original_filename',
        'mime_type',
        'size',
        'status',
        'tenant_visible',
        'verified_at',
        'rejected_at',
        'rejection_reason',
        'expires_at',
        'archived_at',
        'created_at',
        'updated_at',
    ];

    private const ADMIN_RELATIONS = [
        'tenant:id,organization_id,name,email,role,status',
        'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
        'property.building:id,organization_id,name,address_line_1,city',
        'uploadedBy:id,organization_id,name,email,role,status',
        'verifiedBy:id,organization_id,name,email,role,status',
        'rejectedBy:id,organization_id,name,email,role,status',
    ];

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'property_id',
        'related_type',
        'related_id',
        'document_type',
        'title',
        'description_for_tenant',
        'internal_note',
        'file_path',
        'original_filename',
        'mime_type',
        'size',
        'status',
        'tenant_visible',
        'uploaded_by_user_id',
        'verified_by_user_id',
        'verified_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'expires_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => TenantDocumentType::class,
            'status' => TenantDocumentStatus::class,
            'tenant_visible' => 'boolean',
            'size' => 'integer',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expires_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeTenantVisible(Builder $query): Builder
    {
        return $query->where('tenant_visible', true);
    }

    public function scopeForDocumentType(Builder $query, string|TenantDocumentType|null $documentType): Builder
    {
        if ($documentType instanceof TenantDocumentType) {
            return $query->where('document_type', $documentType);
        }

        if (blank($documentType)) {
            return $query;
        }

        return $query->where('document_type', $documentType);
    }

    public function scopeVisibleToTenantPortal(Builder $query): Builder
    {
        return $query
            ->select(self::TENANT_PORTAL_COLUMNS)
            ->tenantVisible()
            ->whereIn('status', TenantDocumentStatus::tenantPortalValues())
            ->whereNull('archived_at')
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name,address_line_1,city',
            ]);
    }

    public function scopeWithAdminDocumentRelations(Builder $query): Builder
    {
        return $query->with(self::ADMIN_RELATIONS);
    }

    public function scopeLatestActivityFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function scopeExpiredUnmarked(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereIn('status', TenantDocumentStatus::expirableValues());
    }

    public function isVisibleToTenantPortal(): bool
    {
        return $this->tenant_visible
            && $this->archived_at === null
            && in_array($this->status, [
                TenantDocumentStatus::ACTIVE,
                TenantDocumentStatus::PENDING_REVIEW,
                TenantDocumentStatus::VERIFIED,
                TenantDocumentStatus::REJECTED,
                TenantDocumentStatus::EXPIRED,
            ], true);
    }

    public function isExpired(): bool
    {
        return $this->status === TenantDocumentStatus::EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function isKycDocument(): bool
    {
        return $this->document_type?->isKyc() ?? false;
    }

    public function requiresTenantSafeMetadata(): bool
    {
        return $this->tenant_visible;
    }
}
