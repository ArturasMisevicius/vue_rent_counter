<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'attachable_type',
        'attachable_id',
        'uploaded_by_user_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'document_type',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeUploadedBy(Builder $query, int $userId): Builder
    {
        return $query->where('uploaded_by_user_id', $userId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeForDocumentType(Builder $query, string|array|null $documentType): Builder
    {
        if (blank($documentType)) {
            return $query;
        }

        return is_array($documentType)
            ? $query->whereIn('document_type', $documentType)
            : $query->where('document_type', $documentType);
    }

    public function scopeWithUploaderSummary(Builder $query): Builder
    {
        return $query->with([
            'uploader:id,name,email',
        ]);
    }
}
