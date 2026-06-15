<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantKycDocumentStatus;
use App\Enums\TenantKycDocumentType;
use Database\Factories\TenantKycDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantKycDocument extends Model
{
    /** @use HasFactory<TenantKycDocumentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'kyc_profile_id',
        'document_type',
        'document_number_encrypted',
        'issued_country',
        'issued_at',
        'expires_at',
        'status',
        'file_document_id',
        'submitted_by_user_id',
        'submitted_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'internal_note',
        'replaced_by_document_id',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => TenantKycDocumentType::class,
            'document_number_encrypted' => 'encrypted',
            'status' => TenantKycDocumentStatus::class,
            'issued_at' => 'date',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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

    public function profile(): BelongsTo
    {
        return $this->belongsTo(TenantKycProfile::class, 'kyc_profile_id');
    }

    public function fileDocument(): BelongsTo
    {
        return $this->belongsTo(TenantDocument::class, 'file_document_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_document_id');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForDocumentType(Builder $query, TenantKycDocumentType|string|null $documentType): Builder
    {
        if ($documentType instanceof TenantKycDocumentType) {
            return $query->where('document_type', $documentType);
        }

        if (blank($documentType)) {
            return $query;
        }

        return $query->where('document_type', $documentType);
    }

    public function scopeActiveForChecklist(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereIn('status', TenantKycDocumentStatus::activeChecklistValues());
    }

    public function scopeExpiredUnmarked(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereIn('status', TenantKycDocumentStatus::expirableValues());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->whereIn('status', TenantKycDocumentStatus::activeChecklistValues());
    }

    public function scopeWithReviewRelations(Builder $query): Builder
    {
        return $query->with([
            'tenant:id,organization_id,name,email,role,status',
            'profile:id,organization_id,tenant_id,status',
            'fileDocument:id,organization_id,tenant_id,title,original_filename,mime_type,size,status,tenant_visible,expires_at',
            'submittedBy:id,organization_id,name,email,role,status',
            'reviewedBy:id,organization_id,name,email,role,status',
        ]);
    }

    public function scopeLatestActivityFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function isExpired(): bool
    {
        return $this->status === TenantKycDocumentStatus::EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }
}
