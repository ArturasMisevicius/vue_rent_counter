<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'slug',
        'color',
        'description',
        'type',
        'is_system',
        'created_at',
        'updated_at',
    ];

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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('type')
            ->orderBy('name')
            ->orderBy('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->ordered();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', (int) $organizationId);
    }
}
