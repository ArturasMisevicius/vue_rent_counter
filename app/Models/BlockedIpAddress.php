<?php

namespace App\Models;

use Database\Factories\BlockedIpAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIpAddress extends Model
{
    /** @use HasFactory<BlockedIpAddressFactory> */
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_until',
        'blocked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
        ];
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
    }
}
