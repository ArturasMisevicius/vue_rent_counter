<?php

namespace App\Models;

use Database\Factories\PlatformOrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlatformOrganizationInvitation extends Model
{
    /** @use HasFactory<PlatformOrganizationInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_name',
        'admin_email',
        'plan_type',
        'max_properties',
        'max_users',
        'token',
        'status',
        'expires_at',
        'accepted_at',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation): void {
            if (blank($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            if ($invitation->expires_at === null) {
                $invitation->expires_at = now()->addDays(7);
            }

            if (blank($invitation->status)) {
                $invitation->status = 'pending';
            }
        });
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
