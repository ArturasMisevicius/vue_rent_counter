<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

/**
 * HasTags Trait
 * 
 * Add this trait to any model that should support tagging
 */
trait HasTags
{
    /**
     * Get all tags for the model
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Attach tags to the model
     * 
     * @param array|Collection|Tag $tags
     * @param int|null $taggedBy
     */
    public function attachTags($tags, ?int $taggedBy = null): void
    {
        $tagIds = $this->parseTagIds($tags);
        
        $pivotData = [];
        foreach ($tagIds as $tagId) {
            $pivotData[$tagId] = ['tagged_by' => $taggedBy ?? auth()->id()];
        }

        $this->tags()->syncWithoutDetaching($pivotData);
        
        // Update usage counts efficiently using bulk operations
        Tag::bulkUpdateUsageCounts($tagIds);
    }

    /**
     * Detach tags from the model
     * 
     * @param array|Collection|Tag|null $tags
     */
    public function detachTags($tags = null): void
    {
        if ($tags === null) {
            $tagIds = $this->tags()->pluck('tags.id');
            $this->tags()->detach();
        } else {
            $tagIds = $this->parseTagIds($tags);
            $this->tags()->detach($tagIds);
        }

        // Update usage counts
        Tag::whereIn('id', $tagIds)->each(fn($tag) => $tag->updateUsageCount());
    }

    /**
     * Sync tags (replace all existing tags)
     * 
     * @param array|Collection $tags
     * @param int|null $taggedBy
     */
    public function syncTags($tags, ?int $taggedBy = null): void
    {
        $oldTagIds = $this->tags()->pluck('tags.id');
        
        $tagIds = $this->parseTagIds($tags);
        
        $pivotData = [];
        foreach ($tagIds as $tagId) {
            $pivotData[$tagId] = ['tagged_by' => $taggedBy ?? auth()->id()];
        }

        $this->tags()->sync($pivotData);
        
        // Update usage counts for old and new tags
        $affectedTagIds = $oldTagIds->merge($tagIds)->unique();
        Tag::whereIn('id', $affectedTagIds)->each(fn($tag) => $tag->updateUsageCount());
    }

    /**
     * Check if model has a specific tag
     * 
     * @param string|Tag $tag
     */
    public function hasTag($tag): bool
    {
        if (is_string($tag)) {
            return $this->tags()->where('slug', $tag)->exists();
        }

        return $this->tags()->where('tags.id', $tag->id)->exists();
    }

    /**
     * Check if model has any of the given tags
     * 
     * @param array $tags
     */
    public function hasAnyTag(array $tags): bool
    {
        $tagIds = $this->parseTagIds($tags);
        return $this->tags()->whereIn('tags.id', $tagIds)->exists();
    }

    /**
     * Check if model has all of the given tags
     * 
     * @param array $tags
     */
    public function hasAllTags(array $tags): bool
    {
        $tagIds = $this->parseTagIds($tags);
        return $this->tags()->whereIn('tags.id', $tagIds)->count() === count($tagIds);
    }

    /**
     * Get tag names as array
     */
    public function getTagNamesAttribute(): array
    {
        return $this->tags->pluck('name')->toArray();
    }

    /**
     * Scope: Filter models with specific tag
     */
    public function scopeWithTag($query, $tag)
    {
        if (is_string($tag)) {
            return $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        return $query->whereHas('tags', function ($q) use ($tag) {
            $q->where('tags.id', $tag->id);
        });
    }

    /**
     * Scope: Filter models with any of the given tags
     */
    public function scopeWithAnyTag($query, array $tags)
    {
        $tagIds = $this->parseTagIds($tags);
        
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('tags.id', $tagIds);
        });
    }

    /**
     * Scope: Filter models with all of the given tags
     */
    public function scopeWithAllTags($query, array $tags)
    {
        $tagIds = $this->parseTagIds($tags);
        
        foreach ($tagIds as $tagId) {
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        return $query;
    }

    /**
     * Parse tag IDs from various input formats
     */
    protected function parseTagIds($tags): array
    {
        if ($tags instanceof Collection) {
            return $tags->pluck('id')->toArray();
        }

        if ($tags instanceof Tag) {
            return [$tags->id];
        }

        if (is_array($tags)) {
            return collect($tags)->map(function ($tag) {
                if ($tag instanceof Tag) {
                    return $tag->id;
                }
                if (is_numeric($tag)) {
                    return $tag;
                }
                // Assume it's a slug
                return Tag::where('slug', $tag)->first()?->id;
            })->filter()->toArray();
        }

        return [];
    }
}
