<?php

namespace App\Models;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\CommentReactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    /** @use HasFactory<CommentReactionFactory> */
    use HasFactory;

    private const SUPERADMIN_INDEX_COLUMNS = [
        'id',
        'comment_id',
        'user_id',
        'type',
        'created_at',
        'updated_at',
    ];

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

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'comment:id,body',
            'user:id,name',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUPERADMIN_INDEX_COLUMNS)
            ->withIndexRelations()
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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
