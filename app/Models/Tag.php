<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'color',
        'description',
        'type',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tag): void {
            if (blank($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable')
            ->withPivot(['tagged_by_user_id'])
            ->withTimestamps();
    }
}
