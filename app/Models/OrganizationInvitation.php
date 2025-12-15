<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'organization_name',
        'email',
        'role',
        'plan_type',
        'max_properties',
        'max_users',
        'status',
        'token',
        'expires_at',
        'accepted_at',
        'invited_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'max_properties' => 'integer',
        'max_users' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrganizationInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }

            if (empty($invitation->status)) {
                $invitation->status = 'pending';
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function accept(): void
    {
        $this->update(['accepted_at' => now()]);
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isExpired();
    }

    /**
     * Cancel the invitation.
     */
    public function cancel(): void
    {
        $this->delete();
    }

    /**
     * Resend the invitation with a new token and expiry date.
     */
    public function resend(): void
    {
        $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
