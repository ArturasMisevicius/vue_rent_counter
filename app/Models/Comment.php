<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Comment extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
        'content',
        'type',
        'is_internal',
        'is_pinned',
        'lft',
        'rgt',
        'depth',
        'metadata',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
        'metadata' => 'array',
    ];

    public function getContentAttribute(): ?string
    {
        /** @var string|null $body */
        $body = $this->attributes['body'] ?? null;

        return $body;
    }

    public function setContentAttribute(?string $value): void
    {
        $this->attributes['body'] = $value;
    }

    // ==================== RELATIONSHIPS ====================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relationship - can comment on any model
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    // Self-referencing relationships for nested comments
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('lft');
    }

    // Recursive relationship for all descendants (nested set model)
    public function descendants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with('descendants')
            ->orderBy('lft');
    }

    // Get all ancestors (path to root)
    public function ancestors(): HasMany
    {
        return $this->hasMany(self::class)
            ->where('lft', '<', $this->lft)
            ->where('rgt', '>', $this->rgt)
            ->orderBy('lft');
    }

    // ==================== NESTED SET METHODS ====================

    public function makeRoot(): self
    {
        $this->lft = 1;
        $this->rgt = 2;
        $this->depth = 0;
        $this->parent_id = null;
        $this->save();

        return $this;
    }

    public function appendToNode(Comment $parent): self
    {
        // Update nested set values for new child
        $this->parent_id = $parent->id;
        $this->depth = $parent->depth + 1;

        // Make space for new node
        self::where('rgt', '>=', $parent->rgt)
            ->increment('rgt', 2);
        self::where('lft', '>', $parent->rgt)
            ->increment('lft', 2);

        $this->lft = $parent->rgt;
        $this->rgt = $parent->rgt + 1;
        $this->save();

        // Update parent's rgt value
        $parent->increment('rgt', 2);

        return $this;
    }

    // ==================== QUERY SCOPES ====================

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeLeaves($query)
    {
        return $query->whereRaw('rgt = lft + 1');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeThreaded($query)
    {
        return $query->orderBy('lft');
    }

    // ==================== ACCESSORS ====================

    public function getIsRootAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function getIsLeafAttribute(): bool
    {
        return $this->rgt === $this->lft + 1;
    }

    public function getChildrenCountAttribute(): int
    {
        return (int) (($this->rgt - $this->lft - 1) / 2);
    }

    // ==================== HELPER METHODS ====================

    public function getThread(): \Illuminate\Database\Eloquent\Collection
    {
        // Get root comment and all its descendants
        $root = $this->isRoot ? $this : $this->ancestors()->roots()->first();

        return self::where('lft', '>=', $root->lft)
            ->where('rgt', '<=', $root->rgt)
            ->where('commentable_type', $this->commentable_type)
            ->where('commentable_id', $this->commentable_id)
            ->orderBy('lft')
            ->get();
    }

    public function canBeEditedBy(User $user): bool
    {
        // User can edit their own comments within 15 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 15;
        }

        // Admins and managers can edit any comment
        return in_array($user->role, ['admin', 'manager']);
    }

    public function canBeDeletedBy(User $user): bool
    {
        // Can't delete if has children
        if (! $this->isLeaf) {
            return false;
        }

        // User can delete their own comments
        if ($this->user_id === $user->id) {
            return true;
        }

        // Admins can delete any comment
        return $user->role === 'admin';
    }
}
