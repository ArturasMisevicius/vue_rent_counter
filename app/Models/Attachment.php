<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
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
}
