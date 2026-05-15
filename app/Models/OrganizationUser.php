<?php

declare(strict_types=1);

namespace App\Models;

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
        'permissions',
        'joined_at',
        'left_at',
        'is_active',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
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

    public function roleLabel(): string
    {
        $role = UserRole::tryFrom((string) $this->role);

        if ($role instanceof UserRole) {
            return $role->label();
        }

        return LocalizedCodeLabel::translate('superadmin.relation_resources.organization_users.roles', $this->role);
    }
}
