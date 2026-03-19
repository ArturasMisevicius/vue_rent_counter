<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comment Reaction Model - User reactions to comments
 *
 * @property int $id
 * @property int $comment_id
 * @property int $user_id
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CommentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'user_id',
        'type',
    ];

    /**
     * Available reaction types
     */
    public const TYPES = [
        'like' => '👍',
        'dislike' => '👎',
        'heart' => '❤️',
        'laugh' => '😂',
        'wow' => '😮',
        'angry' => '😠',
        'sad' => '😢',
    ];

    /**
     * Get the comment this reaction belongs to
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Get the user who made this reaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the emoji for this reaction type
     */
    public function getEmoji(): string
    {
        return self::TYPES[$this->type] ?? '👍';
    }

    /**
     * Check if reaction type is valid
     */
    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    /**
     * Scope: Reactions of specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Positive reactions
     */
    public function scopePositive($query)
    {
        return $query->whereIn('type', ['like', 'heart', 'laugh', 'wow']);
    }

    /**
     * Scope: Negative reactions
     */
    public function scopeNegative($query)
    {
        return $query->whereIn('type', ['dislike', 'angry', 'sad']);
    }
}
