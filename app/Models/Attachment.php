<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Attachment Model - Polymorphic file attachments
 * 
 * Can attach files to any model (Invoice, Property, Meter, etc.)
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $attachable_id
 * @property string $attachable_type
 * @property int $uploaded_by
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property string|null $description
 * @property array|null $metadata
 */
class Attachment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'attachable_id',
        'attachable_type',
        'uploaded_by',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'description',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the parent attachable model
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size;
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Delete the physical file when model is deleted
     */
    protected static function booted(): void
    {
        static::deleted(function (Attachment $attachment) {
            if ($attachment->isForceDeleting()) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    /**
     * Scope: Only images
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: Only documents
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
