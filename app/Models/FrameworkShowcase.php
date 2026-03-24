<?php

namespace App\Models;

use App\Models\Concerns\HasGeneratedSlug;
use Database\Factories\FrameworkShowcaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrameworkShowcase extends Model
{
    /** @use HasFactory<FrameworkShowcaseFactory> */
    use HasFactory;

    use HasGeneratedSlug;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'title',
        'slug',
        'status',
        'summary',
        'content',
        'meta_title',
        'meta_description',
        'featured_description',
        'thumbnail_path',
        'tags',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    public function publish(): void
    {
        $this->forceFill([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ])->save();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
