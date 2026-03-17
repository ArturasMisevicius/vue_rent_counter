<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'inviter_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function scopeForAcceptancePortal(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'inviter_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'expires_at',
                'accepted_at',
            ])
            ->with([
                'organization:id,name',
            ]);
    }

    public function isAccepted(): bool
    {
        return filled($this->accepted_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }
}
