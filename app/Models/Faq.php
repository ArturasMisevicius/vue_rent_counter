<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    use SoftDeletes;

    private const PUBLISHED_COLUMNS = [
        'id',
        'question',
        'answer',
        'category',
        'display_order',
        'is_published',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'question',
        'answer',
        'category',
        'display_order',
        'is_published',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->select(self::PUBLISHED_COLUMNS)
            ->where('is_published', true)
            ->orderedForDisplay();
    }

    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
