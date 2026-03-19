<?php

declare(strict_types=1);

namespace App\Filament\Support\Workspace;

use App\Enums\UserRole;

final readonly class WorkspaceContext
{
    public function __construct(
        public int $userId,
        public UserRole $role,
        public ?int $organizationId,
        public ?int $propertyId,
    ) {}

    public function isPlatform(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    public function isAdminLike(): bool
    {
        return in_array($this->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true);
    }

    public function isAdminOrManager(): bool
    {
        return in_array($this->role, [
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true);
    }

    public function isTenant(): bool
    {
        return $this->role === UserRole::TENANT;
    }

    public function scope(): string
    {
        if ($this->isPlatform()) {
            return 'platform';
        }

        if ($this->isTenant()) {
            return 'tenant';
        }

        return 'organization';
    }

    /**
     * @return array{
     *     scope: string,
     *     role: string,
     *     user_id: int,
     *     organization_id: int|null,
     *     property_id: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope(),
            'role' => $this->role->value,
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
            'property_id' => $this->propertyId,
        ];
    }
}
