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
        'edited_at',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
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
}
