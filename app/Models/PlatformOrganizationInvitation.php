<?php

namespace App\Models;

use Database\Factories\PlatformOrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlatformOrganizationInvitation extends Model
{
    /** @use HasFactory<PlatformOrganizationInvitationFactory> */
    use HasFactory;

    private const PENDING_STATUS = 'pending';

    private const SUMMARY_COLUMNS = [
        'id',
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
        'created_at',
        'updated_at',
    ];

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
            'max_properties' => 'integer',
            'max_users' => 'integer',
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
                $invitation->status = self::PENDING_STATUS;
            }
        });
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->where('status', self::PENDING_STATUS)
            ->whereNull('accepted_at')
            ->unexpired();
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>=', now());
    }

    public function scopeForAdminEmail(Builder $query, string $email): Builder
    {
        return $query->forEmail($email);
    }

    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('admin_email', $email);
    }

    public function scopeWithInviterSummary(Builder $query): Builder
    {
        return $query->with([
            'inviter:id,name,email,role,status',
        ]);
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeForControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withInviterSummary()
            ->recentFirst();
    }
}
