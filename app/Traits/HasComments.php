<?php

namespace App\Traits;

use App\Models\Comment;
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
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only top-level comments (no replies)
     */
    public function topLevelComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only internal comments
     */
    public function internalComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->where('is_internal', true)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get only public comments
     */
    public function publicComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->where('is_internal', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get pinned comments
     */
    public function pinnedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->where('is_pinned', true)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Add a comment to the model
     */
    public function addComment(string $body, int $userId, bool $isInternal = false): Comment
    {
        $comment = $this->comments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()->tenant_id,
            'user_id' => $userId,
            'body' => $body,
            'is_internal' => $isInternal,
            'moderation_status' => 'pending', // All comments start as pending
        ]);

        // Trigger automatic moderation
        $moderationService = app(\App\Services\ContentModerationService::class);
        $moderationService->moderateComment($comment);

        return $comment;
    }

    /**
     * Report a comment
     */
    public function reportComment(int $commentId, int $reportedBy, string $reason, string $description = null): \App\Models\CommentReport
    {
        $comment = $this->comments()->findOrFail($commentId);
        
        // Create the report
        $report = \App\Models\CommentReport::create([
            'tenant_id' => $this->tenant_id ?? auth()->user()->tenant_id,
            'comment_id' => $commentId,
            'reported_by' => $reportedBy,
            'reason' => $reason,
            'description' => $description,
        ]);

        // Update comment report count
        $comment->reportByUser();

        return $report;
    }

    /**
     * Get total comment count
     */
    public function getCommentCountAttribute(): int
    {
        return $this->comments()->count();
    }
}
