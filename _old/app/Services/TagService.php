<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TagService - Advanced tag management with performance optimizations
 * 
 * Provides high-level tag operations with bulk operations and
 * performance optimizations for the tagging system.
 */
class TagService
{
    /**
     * Bulk tag multiple models efficiently
     * 
     * @param array<Model> $models
     * @param array<string> $tagNames
     * @param int $tenantId
     * @param int|null $taggedBy
     */
    public function bulkTagModels(array $models, array $tagNames, int $tenantId, ?int $taggedBy = null): void
    {
        if (empty($models) || empty($tagNames)) {
            return;
        }

        DB::transaction(function () use ($models, $tagNames, $tenantId, $taggedBy) {
            // Get or create all tags at once
            $tags = $this->getOrCreateTagsByNames($tagNames, $tenantId);
            $tagIds = $tags->pluck('id')->toArray();

            // Prepare bulk insert data for pivot table
            $pivotData = [];
            $userId = $taggedBy ?? auth()->id();

            foreach ($models as $model) {
                foreach ($tagIds as $tagId) {
                    $pivotData[] = [
                        'tag_id' => $tagId,
                        'taggable_id' => $model->id,
                        'taggable_type' => get_class($model),
                        'tagged_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert pivot records
            DB::table('taggables')->insertOrIgnore($pivotData);

            // Update usage counts efficiently
            Tag::bulkUpdateUsageCounts($tagIds);
        });
    }

    /**
     * Get or create tags by names
     * 
     * @param array<string> $names
     * @param int $tenantId
     * @return Collection<Tag>
     */
    private function getOrCreateTagsByNames(array $names, int $tenantId): Collection
    {
        $slugs = array_map(fn($name) => \Str::slug($name), $names);
        
        // Get existing tags
        $existing = Tag::where('tenant_id', $tenantId)
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        $tags = collect();
        
        foreach ($names as $name) {
            $slug = \Str::slug($name);
            
            if ($existing->has($slug)) {
                $tags->push($existing->get($slug));
            } else {
                // Create new tag
                $tag = Tag::create([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'slug' => $slug,
                    'usage_count' => 0,
                ]);
                $tags->push($tag);
            }
        }

        return $tags;
    }

    /**
     * Clean up unused tags for a tenant
     * 
     * @param int $tenantId
     * @param int $daysUnused
     * @return int Number of tags deleted
     */
    public function cleanupUnusedTags(int $tenantId, int $daysUnused = 30): int
    {
        $cutoffDate = now()->subDays($daysUnused);

        return Tag::where('tenant_id', $tenantId)
            ->where('usage_count', 0)
            ->where('updated_at', '<', $cutoffDate)
            ->delete();
    }
}