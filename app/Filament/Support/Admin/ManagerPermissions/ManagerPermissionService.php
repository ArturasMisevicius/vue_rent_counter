<?php

namespace App\Filament\Support\Admin\ManagerPermissions;

use App\Enums\AuditLogAction;
use App\Enums\UserRole;
use App\Exceptions\ManagerPermissions\InvalidPermissionActionException;
use App\Exceptions\ManagerPermissions\InvalidPermissionResourceException;
use App\Exceptions\ManagerPermissions\UserIsNotManagerException;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Notifications\Admin\ManagerPermissionsUpdatedNotification;
use Illuminate\Support\Facades\DB;

class ManagerPermissionService
{
    /**
     * @var array<string, array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>>
     */
    private static array $matrixCache = [];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public static function flushCache(): void
    {
        self::$matrixCache = [];
    }

    /**
     * @return array<string, array{can_create: bool, can_edit: bool, can_delete: bool}>
     */
    public function getMatrix(User $manager, Organization $organization): array
    {
        $cacheKey = $this->cacheKey($manager, $organization);

        if (array_key_exists($cacheKey, self::$matrixCache)) {
            return self::$matrixCache[$cacheKey];
        }

        $matrix = ManagerPermissionCatalog::defaultMatrix();
        $persisted = ManagerPermission::allForManager($manager, $organization);

        foreach ($persisted as $resource => $permission) {
            $matrix[$resource] = [
                'can_create' => (bool) $permission->can_create,
                'can_edit' => (bool) $permission->can_edit,
                'can_delete' => (bool) $permission->can_delete,
            ];
        }

        return self::$matrixCache[$cacheKey] = $matrix;
    }

    /**
     * @param  array<string, array{can_create?: bool, can_edit?: bool, can_delete?: bool}>  $matrix
     */
    public function saveMatrix(User $manager, Organization $organization, array $matrix, User $actor): void
    {
        $this->ensureManagerForOrganization($manager, $organization);
        $this->ensureValidResources($matrix);

        $before = $this->getMatrix($manager, $organization);
        $after = ManagerPermissionCatalog::normalizeMatrix($matrix);

        DB::transaction(function () use ($after, $manager, $organization): void {
            ManagerPermission::syncForManager($manager, $organization, $after);

            unset(self::$matrixCache[$this->cacheKey($manager, $organization)]);
        });

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $organization,
            [
                'context' => [
                    'mutation' => 'manager_permissions.updated',
                    'actor_type' => $actor->isSuperadmin() ? 'superadmin' : 'organization_user',
                ],
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                ],
                'before' => $before,
                'after' => $after,
            ],
            actorUserId: $actor->id,
            description: "Manager permissions updated for {$manager->name}",
        );

        $manager->notify(new ManagerPermissionsUpdatedNotification($actor, $organization));
    }

    public function can(User $manager, Organization $organization, string $resource, string $action): bool
    {
        if (! ManagerPermissionCatalog::isValidAction($action)) {
            throw InvalidPermissionActionException::unknown($action);
        }

        if (! ManagerPermissionCatalog::isValidResource($resource)) {
            throw InvalidPermissionResourceException::unknown($resource);
        }

        if (! $this->isManagerForOrganization($manager, $organization)) {
            return false;
        }

        $matrix = $this->getMatrix($manager, $organization);

        return (bool) ($matrix[$resource][ManagerPermissionCatalog::flagForAction($action)] ?? false);
    }

    public function resetToDefaults(User $manager, Organization $organization, User $actor): void
    {
        $before = $this->getMatrix($manager, $organization);

        DB::transaction(function () use ($manager, $organization): void {
            ManagerPermission::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $manager->id)
                ->delete();

            unset(self::$matrixCache[$this->cacheKey($manager, $organization)]);
        });

        $this->auditLogger->record(
            AuditLogAction::DELETED,
            $organization,
            [
                'context' => [
                    'mutation' => 'manager_permissions.reset',
                    'actor_type' => $actor->isSuperadmin() ? 'superadmin' : 'organization_user',
                ],
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                ],
                'before' => $before,
                'after' => ManagerPermissionCatalog::defaultMatrix(),
            ],
            actorUserId: $actor->id,
            description: "Manager permissions reset for {$manager->name}",
        );

        $manager->notify(new ManagerPermissionsUpdatedNotification(
            $actor,
            $organization,
            'admin.manager_permissions.notifications.reset',
        ));
    }

    public function copyFromManager(User $sourceManager, User $targetManager, Organization $organization, User $actor): void
    {
        $this->ensureManagerForOrganization($sourceManager, $organization);
        $this->ensureManagerForOrganization($targetManager, $organization);

        $before = $this->getMatrix($targetManager, $organization);
        $after = $this->getMatrix($sourceManager, $organization);

        DB::transaction(function () use ($after, $organization, $targetManager): void {
            ManagerPermission::syncForManager($targetManager, $organization, $after);

            unset(self::$matrixCache[$this->cacheKey($targetManager, $organization)]);
        });

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $organization,
            [
                'context' => [
                    'mutation' => 'manager_permissions.copied',
                    'actor_type' => $actor->isSuperadmin() ? 'superadmin' : 'organization_user',
                ],
                'source_manager' => [
                    'id' => $sourceManager->id,
                    'name' => $sourceManager->name,
                    'email' => $sourceManager->email,
                ],
                'target_manager' => [
                    'id' => $targetManager->id,
                    'name' => $targetManager->name,
                    'email' => $targetManager->email,
                ],
                'before' => $before,
                'after' => $after,
            ],
            actorUserId: $actor->id,
            description: "Manager permissions copied from {$sourceManager->name} to {$targetManager->name}",
        );

        $targetManager->notify(new ManagerPermissionsUpdatedNotification(
            $actor,
            $organization,
            'admin.manager_permissions.notifications.copied',
        ));
    }

    public function isManagerForOrganization(User $user, Organization $organization): bool
    {
        if ($user->organization_id === $organization->id && $user->role === UserRole::MANAGER) {
            return true;
        }

        return OrganizationUser::query()
            ->active()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('role', UserRole::MANAGER->value)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $matrix
     */
    private function ensureValidResources(array $matrix): void
    {
        foreach (array_keys($matrix) as $resource) {
            if (! is_string($resource) || ! ManagerPermissionCatalog::isValidResource($resource)) {
                throw InvalidPermissionResourceException::unknown((string) $resource);
            }
        }
    }

    private function ensureManagerForOrganization(User $manager, Organization $organization): void
    {
        if (! $this->isManagerForOrganization($manager, $organization)) {
            throw UserIsNotManagerException::forUser($manager, $organization);
        }
    }

    private function cacheKey(User $user, Organization $organization): string
    {
        return "{$organization->getKey()}:{$user->getKey()}";
    }
}
