<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitationStatus;
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
        'tenant_id',
        'inviter_user_id',
        'invited_by_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'token_hash',
        'sent_at',
        'expires_at',
        'accepted_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ];

    private const HASHED_TOKEN_LENGTH = 64;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'inviter_user_id',
        'invited_by_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'token_hash',
        'sent_at',
        'expires_at',
        'accepted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function scopeForAcceptancePortal(Builder $query): Builder
    {
        return $query
            ->select(self::ACCEPTANCE_COLUMNS)
            ->withAcceptanceSummary()
            ->latestSentFirst();
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, User|int $tenant): Builder
    {
        $tenantId = $tenant instanceof User ? $tenant->id : $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForToken(Builder $query, string $token): Builder
    {
        $normalizedToken = trim($token);

        if ($normalizedToken === '') {
            return $query->whereKey(0);
        }

        $tokenHash = self::hashToken($normalizedToken);

        return $query->where(function (Builder $query) use ($tokenHash): void {
            $query
                ->where('token_hash', $tokenHash)
                ->orWhere('token', $tokenHash);
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
            ->whereNull('revoked_at')
            ->unexpired();
    }

    public function scopePendingForEmail(Builder $query, string $email): Builder
    {
        return $query
            ->pending()
            ->where('email', $email);
    }

    public function scopePendingForTenant(Builder $query, User $tenant): Builder
    {
        return $query
            ->pending()
            ->forTenant($tenant);
    }

    public function scopeLatestExpiryFirst(Builder $query): Builder
    {
        return $query
            ->orderBy('expires_at')
            ->orderByDesc('id');
    }

    public function scopeLatestSentFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }

    public function scopeWithAcceptanceSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
            'tenant:id,organization_id,name,email,role,status,tenant_status,portal_access_enabled,locale',
            'tenant.currentPropertyAssignment:id,organization_id,property_id,tenant_user_id,unit_area_sqm,assigned_at,unassigned_at',
            'tenant.currentPropertyAssignment.property:id,organization_id,building_id,name,floor,unit_number,type,floor_area_sqm',
            'tenant.currentPropertyAssignment.property.building:id,organization_id,name,address_line_1,city',
            'inviter:id,organization_id,name,email,role,status',
            'invitedBy:id,organization_id,name,email,role,status',
        ]);
    }

    public function isAccepted(): bool
    {
        return filled($this->accepted_at);
    }

    public function isRevoked(): bool
    {
        return filled($this->revoked_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isRevoked() && ! $this->isExpired();
    }

    public function invitationStatus(): InvitationStatus
    {
        return match (true) {
            $this->isAccepted() => InvitationStatus::ACCEPTED,
            $this->isRevoked() => InvitationStatus::REVOKED,
            $this->isExpired() => InvitationStatus::EXPIRED,
            default => InvitationStatus::PENDING,
        };
    }

    public function routeToken(): string
    {
        return $this->acceptanceToken ?? '';
    }

    public static function issueToken(): string
    {
        return Str::random(64);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function isHashedToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{'.self::HASHED_TOKEN_LENGTH.'}$/', (string) $token);
    }
}
