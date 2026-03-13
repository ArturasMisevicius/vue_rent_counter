<?php

namespace App\Traits;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasComments Trait
 * 
 * Add this trait to any model that should support comments
 */
trait HasComments
{
    /**
     * Get all comments for the model
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('path')
            ->orderBy('sort_order');
    }

    /**
     * Get only top-level comments
     */
    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Get only internal comments
     */
    public function internalComments(): MorphMany
    {
        return $this->comments()->where('is_internal', true);
    }

    /**
     * Get only public comments
     */
    public function publicComments(): MorphMany
    {
        return $this->comments()->where('is_internal', false);
    }

    /**
     * Get unresolved comments
     */
    public function unresolvedComments(): MorphMany
    {
        return $this->comments()->where('is_resolved', false);
    }

    /**
     * Get pinned comments
     */
    public function pinnedComments(): MorphMany
    {
        return $this->comments()->where('is_pinned', true);
    }

    /**
     * Add a comment
     */
    public function addComment(
        string $body, 
        ?User $user = null, 
        bool $isInternal = false,
        ?Comment $parent = null
    ): Comment {
        $depth = $parent ? $parent->depth + 1 : 0;
        $path = $parent ? $parent->path . '.' . $parent->id : null;

        $comment = $this->comments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()?->tenant_id,
            'user_id' => $user?->id ?? auth()->id(),
            'parent_id' => $parent?->id,
            'body' => $body,
            'is_internal' => $isInternal,
            'depth' => $depth,
            'path' => $path,
            'sort_order' => $this->getNextSortOrder($parent),
        ]);

        // Update path after creation if it's a root comment
        if (!$parent) {
            $comment->update(['path' => (string) $comment->id]);
        }

        return $comment;
    }

    /**
     * Reply to a comment
     */
    public function replyToComment(
        Comment $parentComment, 
        string $body, 
        ?User $user = null, 
        bool $isInternal = false
    ): Comment {
        return $this->addComment($body, $user, $isInternal, $parentComment);
    }

    /**
     * Get comment count
     */
    public function getCommentCountAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Get unresolved comment count
     */
    public function getUnresolvedCommentCountAttribute(): int
    {
        return $this->comments()->where('is_resolved', false)->count();
    }

    /**
     * Get public comment count
     */
    public function getPublicCommentCountAttribute(): int
    {
        return $this->comments()->where('is_internal', false)->count();
    }

    /**
     * Get internal comment count
     */
    public function getInternalCommentCountAttribute(): int
    {
        return $this->comments()->where('is_internal', true)->count();
    }

    /**
     * Check if model has unresolved comments
     */
    public function hasUnresolvedComments(): bool
    {
        return $this->comments()->where('is_resolved', false)->exists();
    }

    /**
     * Resolve all comments
     */
    public function resolveAllComments(?User $user = null): void
    {
        $this->comments()
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_by' => $user?->id ?? auth()->id(),
                'resolved_at' => now(),
            ]);
    }

    /**
     * Get next sort order for comments
     */
    protected function getNextSortOrder(?Comment $parent = null): int
    {
        $query = $this->comments();
        
        if ($parent) {
            $query->where('parent_id', $parent->id);
        } else {
            $query->whereNull('parent_id');
        }

        return $query->max('sort_order') + 1;
    }

    /**
     * Scope: Models with comments
     */
    public function scopeWithComments($query)
    {
        return $query->whereHas('comments');
    }

    /**
     * Scope: Models with unresolved comments
     */
    public function scopeWithUnresolvedComments($query)
    {
        return $query->whereHas('comments', function ($q) {
            $q->where('is_resolved', false);
        });
    }

    /**
     * Scope: Models with recent comments
     */
    public function scopeWithRecentComments($query, int $days = 7)
    {
        return $query->whereHas('comments', function ($q) use ($days) {
            $q->where('created_at', '>', now()->subDays($days));
        });
    }
}