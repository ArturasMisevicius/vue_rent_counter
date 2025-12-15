<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comment Model - Polymorphic comments system
 * 
 * Can be attached to any model (Invoice, Property, Meter, Building, etc.)
 * Supports nested comments (replies) and internal notes
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $commentable_id
 * @property string $commentable_type
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property bool $is_internal
 * @property bool $is_pinned
 * @property int $depth
 * @property string|null $path
 * @property int $sort_order
 * @property array|null $mentions
 * @property bool $is_resolved
 * @property int|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon|null $edited_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Comment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'commentable_id',
        'commentable_type',
        'user_id',
        'parent_id',
        'body',
        'is_internal',
        'is_pinned',
        'depth',
        'path',
        'sort_order',
        'mentions',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'edited_at',
        'moderation_status',
        'moderated_by',
        'moderated_at',
        'moderation_reason',
        'spam_score',
        'toxicity_score',
        'moderation_flags',
        'report_count',
        'last_reported_at',
        'sentiment',
        'technical_value',
        'relevance',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
        'mentions' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'edited_at' => 'datetime',
        'moderated_at' => 'datetime',
        'moderation_flags' => 'array',
        'last_reported_at' => 'datetime',
        'spam_score' => 'integer',
        'toxicity_score' => 'integer',
        'report_count' => 'integer',
    ];

    /**
     * Get the parent commentable model (Invoice, Property, etc.)
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who moderated the comment
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get all reports for this comment
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Get the parent comment (for nested comments)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get all replies to this comment
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all descendants (replies and their replies recursively)
     */
    public function descendants(): HasMany
    {
        return $this->replies()->with('descendants');
    }

    /**
     * Scope: Only top-level comments (no parent)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Only internal comments
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Only public comments
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Only pinned comments
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Check if comment has been edited
     */
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /**
     * Check if comment is a reply
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Mark comment as edited
     */
    public function markAsEdited(): void
    {
        $this->edited_at = now();
        $this->save();
    }

    /**
     * Moderation Scopes
     */
    public function scopePending($query)
    {
        return $query->where('moderation_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('moderation_status', 'rejected');
    }

    public function scopeFlagged($query)
    {
        return $query->where('moderation_status', 'flagged');
    }

    public function scopeNeedsModeration($query)
    {
        return $query->whereIn('moderation_status', ['pending', 'flagged']);
    }

    public function scopeHighRisk($query)
    {
        return $query->where(function ($q) {
            $q->where('spam_score', '>', 70)
              ->orWhere('toxicity_score', '>', 70)
              ->orWhere('report_count', '>', 2);
        });
    }

    /**
     * Moderation Methods
     */
    public function approve(int $moderatorId, string $reason = null): void
    {
        $this->update([
            'moderation_status' => 'approved',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);
    }

    public function reject(int $moderatorId, string $reason): void
    {
        $this->update([
            'moderation_status' => 'rejected',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);
    }

    public function flag(int $moderatorId, string $reason): void
    {
        $this->update([
            'moderation_status' => 'flagged',
            'moderated_by' => $moderatorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
        ]);
    }

    public function reportByUser(): void
    {
        $this->increment('report_count');
        $this->update(['last_reported_at' => now()]);
        
        // Auto-flag if too many reports
        if ($this->report_count >= 3 && $this->moderation_status === 'pending') {
            $this->update(['moderation_status' => 'flagged']);
        }
    }

    public function updateModerationScores(int $spamScore, int $toxicityScore, array $flags = []): void
    {
        $this->update([
            'spam_score' => $spamScore,
            'toxicity_score' => $toxicityScore,
            'moderation_flags' => $flags,
        ]);

        // Auto-flag high-risk content
        if ($spamScore > 80 || $toxicityScore > 80) {
            $this->update(['moderation_status' => 'flagged']);
        }
    }

    /**
     * Check if comment needs moderation
     */
    public function needsModeration(): bool
    {
        return in_array($this->moderation_status, ['pending', 'flagged']);
    }

    /**
     * Check if comment is approved
     */
    public function isApproved(): bool
    {
        return $this->moderation_status === 'approved';
    }

    /**
     * Check if comment is high risk
     */
    public function isHighRisk(): bool
    {
        return $this->spam_score > 70 || 
               $this->toxicity_score > 70 || 
               $this->report_count > 2;
    }
}
