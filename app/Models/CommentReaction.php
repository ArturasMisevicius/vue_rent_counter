<?php

namespace App\Models;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\CommentReactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    /** @use HasFactory<CommentReactionFactory> */
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'user_id',
        'type',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return LocalizedCodeLabel::translate('superadmin.relation_resources.comment_reactions.types', $this->type);
    }

    public function typeBadgeColor(): string
    {
        return match ($this->type) {
            'heart' => 'danger',
            'laugh', 'wow' => 'warning',
            default => 'info',
        };
    }
}
