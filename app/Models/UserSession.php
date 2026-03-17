<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $table = 'sessions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'payload' => 'array',
            'last_activity' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActiveSince(Builder $query, \DateTimeInterface $moment): Builder
    {
        return $query->where('last_activity', '>=', $moment->getTimestamp());
    }

    public function scopeLatestActivityFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('last_activity')
            ->orderBy('id');
    }
}
