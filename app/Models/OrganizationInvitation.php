<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\OrganizationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    /** @use HasFactory<OrganizationInvitationFactory> */
    use HasFactory;

    public ?string $acceptanceToken = null;

    private const ACCEPTANCE_COLUMNS = [
        'id',
        'organization_id',
        'inviter_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'expires_at',
        'accepted_at',
        'created_at',
        'updated_at',
    ];

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
            ->select(self::ACCEPTANCE_COLUMNS)
            ->withAcceptanceSummary()
            ->latestExpiryFirst();
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForToken(Builder $query, string $token): Builder
    {
        return $query->where(function (Builder $query) use ($token): void {
            $query
                ->where('token', static::hashToken($token))
                ->orWhere('token', $token);
        });
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>=', now());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('accepted_at')
            ->unexpired();
    }

    public function scopePendingForEmail(Builder $query, string $email): Builder
    {
        return $query
            ->pending()
            ->where('email', $email);
    }

    public function scopeLatestExpiryFirst(Builder $query): Builder
    {
        return $query
            ->orderBy('expires_at')
            ->orderByDesc('id');
    }

    public function scopeWithAcceptanceSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
            'inviter:id,organization_id,name,email,role,status',
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

    public function routeToken(): string
    {
        return $this->acceptanceToken ?? $this->token;
    }

    public static function issueToken(): string
    {
        return Str::random(64);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
