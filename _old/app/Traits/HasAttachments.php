<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * HasAttachments Trait
 * 
 * Add this trait to any model that should support file attachments
 */
trait HasAttachments
{
    /**
     * Get all attachments for the model
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get only image attachments
     */
    public function images(): MorphMany
    {
        return $this->attachments()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get only document attachments
     */
    public function documents(): MorphMany
    {
        return $this->attachments()->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Get attachments by category
     */
    public function attachmentsByCategory(string $category): MorphMany
    {
        return $this->attachments()->where('category', $category);
    }

    /**
     * Get public attachments
     */
    public function publicAttachments(): MorphMany
    {
        return $this->attachments()->where('is_public', true);
    }

    /**
     * Attach a file
     */
    public function attachFile(
        UploadedFile $file, 
        ?string $description = null, 
        ?string $category = null,
        ?User $uploader = null,
        bool $isPublic = false
    ): Attachment {
        $filename = $file->hashName();
        $path = $file->store('attachments', 'public');

        // Generate thumbnail for images
        $thumbnailPath = null;
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailPath = $this->generateThumbnail($file, $path);
        }

        return $this->attachments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()?->tenant_id,
            'uploaded_by' => $uploader?->id ?? auth()->id(),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'public',
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'description' => $description,
            'category' => $category,
            'is_public' => $isPublic,
            'metadata' => $this->extractMetadata($file),
        ]);
    }

    /**
     * Attach multiple files
     */
    public function attachFiles(
        array $files, 
        ?string $category = null,
        ?User $uploader = null,
        bool $isPublic = false
    ): array {
        $attachments = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $attachments[] = $this->attachFile($file, null, $category, $uploader, $isPublic);
            }
        }

        return $attachments;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->attachments()->count();
    }

    /**
     * Get total attachment size in bytes
     */
    public function getTotalAttachmentSizeAttribute(): int
    {
        return $this->attachments()->sum('size');
    }

    /**
     * Get total attachment size in human readable format
     */
    public function getHumanAttachmentSizeAttribute(): string
    {
        $bytes = $this->getTotalAttachmentSizeAttribute();
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if model has attachments
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Check if model has images
     */
    public function hasImages(): bool
    {
        return $this->images()->exists();
    }

    /**
     * Check if model has documents
     */
    public function hasDocuments(): bool
    {
        return $this->documents()->exists();
    }

    /**
     * Delete all attachments
     */
    public function deleteAllAttachments(): void
    {
        $this->attachments()->each(function (Attachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            if ($attachment->thumbnail_path) {
                Storage::disk($attachment->disk)->delete($attachment->thumbnail_path);
            }
            $attachment->delete();
        });
    }

    /**
     * Generate thumbnail for image
     */
    protected function generateThumbnail(UploadedFile $file, string $originalPath): ?string
    {
        // This is a placeholder - implement actual thumbnail generation
        // You might use Intervention Image or similar library
        return null;
    }

    /**
     * Extract metadata from file
     */
    protected function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];

        // Add image-specific metadata
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['aspect_ratio'] = round($imageInfo[0] / $imageInfo[1], 2);
            }
        }

        return $metadata;
    }

    /**
     * Scope: Models with attachments
     */
    public function scopeWithAttachments($query)
    {
        return $query->whereHas('attachments');
    }

    /**
     * Scope: Models with images
     */
    public function scopeWithImages($query)
    {
        return $query->whereHas('images');
    }

    /**
     * Scope: Models with documents
     */
    public function scopeWithDocuments($query)
    {
        return $query->whereHas('documents');
    }

    /**
     * Scope: Models with attachments in category
     */
    public function scopeWithAttachmentsInCategory($query, string $category)
    {
        return $query->whereHas('attachments', function ($q) use ($category) {
            $q->where('category', $category);
        });
    }
}