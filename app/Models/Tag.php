<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

/**
 * Tag Model - Tagging system with morph-to-many
 * 
 * Tags can be attached to multiple model types
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property string|null $description
 * @property int $usage_count
 */
class Tag extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'color',
        'description',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        // Update usage count when tag is attached/detached
        static::saved(function (Tag $tag) {
            $tag->updateUsageCount();
        });
    }

    /**
     * Get all invoices with this tag
     */
    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all properties with this tag
     */
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all meters with this tag
     */
    public function meters(): MorphToMany
    {
        return $this->morphedByMany(Meter::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all buildings with this tag
     */
    public function buildings(): MorphToMany
    {
        return $this->morphedByMany(Building::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all tenants with this tag
     */
    public function tenants(): MorphToMany
    {
        return $this->morphedByMany(Tenant::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Update the usage count based on actual usage
     */
    public function updateUsageCount(): void
    {
        $count = \DB::table('taggables')
            ->where('tag_id', $this->id)
            ->count();

        $this->usage_count = $count;
        $this->saveQuietly();
    }

    /**
     * Scope: Most used tags
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope: Unused tags
     */
    public function scopeUnused($query)
    {
        return $query->where('usage_count', 0);
    }

    /**
     * Bulk update usage counts for multiple tags efficiently
     * 
     * @param array<int> $tagIds
     */
    public static function bulkUpdateUsageCounts(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        // Use a single query with subquery to update all usage counts at once
        \DB::table('tags')
            ->whereIn('id', $tagIds)
            ->update([
                'usage_count' => \DB::raw('(
                    SELECT COUNT(*) 
                    FROM taggables 
                    WHERE taggables.tag_id = tags.id
                )'),
                'updated_at' => now(),
            ]);
    }
}
