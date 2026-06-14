<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\OrganizationUserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Model
{
    /** @use HasFactory<OrganizationUserFactory> */
    use HasFactory;

    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'status',
        'permissions',
        'permissions_preset',
        'joined_at',
        'left_at',
        'is_active',
        'invited_by',
        'invited_by_user_id',
        'invited_at',
        'accepted_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ManagerMembershipStatus::class,
            'permissions' => 'array',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'is_active' => 'boolean',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', ManagerMembershipStatus::ACTIVE)
            ->where('is_active', true)
            ->whereNull('left_at');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function roleLabel(): string
    {
        $role = UserRole::tryFrom((string) $this->role);

        if ($role instanceof UserRole) {
            return $role->label();
        }

        return LocalizedCodeLabel::translate('superadmin.relation_resources.organization_users.roles', $this->role);
    }

    public function statusLabel(): string
    {
        if ($this->status instanceof ManagerMembershipStatus) {
            return $this->status->label();
        }

        return $this->is_active
            ? ManagerMembershipStatus::ACTIVE->label()
            : ManagerMembershipStatus::DISABLED->label();
    }

    public function isInvited(): bool
    {
        return $this->status === ManagerMembershipStatus::INVITED;
    }

    public function isActiveMembership(): bool
    {
        return $this->status === ManagerMembershipStatus::ACTIVE
            && $this->is_active
            && blank($this->left_at);
    }

    public function isDisabled(): bool
    {
        return $this->status === ManagerMembershipStatus::DISABLED;
    }
}
