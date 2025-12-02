<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only image attachments
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->where('mime_type', 'like', 'image/%')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only document attachments
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Attach a file to the model
     */
    public function attachFile(
        UploadedFile $file,
        int $uploadedBy,
        ?string $description = null,
        string $disk = 'local'
    ): Attachment {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            $this->getAttachmentPath(),
            $filename,
            $disk
        );

        return $this->attachments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()->tenant_id,
            'uploaded_by' => $uploadedBy,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'path' => $path,
            'description' => $description,
            'metadata' => [
                'uploaded_at' => now()->toIso8601String(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Get the storage path for attachments
     */
    protected function getAttachmentPath(): string
    {
        $modelName = strtolower(class_basename($this));
        return "attachments/{$modelName}/{$this->id}";
    }

    /**
     * Get total attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->attachments()->count();
    }

    /**
     * Get total size of all attachments in bytes
     */
    public function getTotalAttachmentSizeAttribute(): int
    {
        return $this->attachments()->sum('size');
    }

    /**
     * Delete all attachments when model is deleted
     */
    protected static function bootHasAttachments(): void
    {
        static::deleting(function ($model) {
            $model->attachments()->each(function ($attachment) {
                $attachment->delete();
            });
        });
    }
}
