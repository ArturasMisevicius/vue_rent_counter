<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantAuditLoggerInterface;
use App\Contracts\TenantAuthorizationServiceInterface;
use App\Contracts\TenantContextInterface;
use App\Exceptions\UnauthorizedTenantSwitchException;
use App\Models\User;
use App\Repositories\TenantRepositoryInterface;
use App\ValueObjects\TenantId;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Session-backed tenant context with authorization and audit logging.
 */
final readonly class TenantContext implements TenantContextInterface
{
    private const SESSION_KEY = 'tenant_context';

    public function __construct(
        private Session $session,
        private TenantRepositoryInterface $tenantRepository,
        private TenantAuthorizationServiceInterface $authorizationService,
        private TenantAuditLoggerInterface $auditLogger
    ) {}

    public function get(): ?int
    {
        $tenantId = $this->session->get(self::SESSION_KEY);

        return is_int($tenantId) && $tenantId > 0 ? $tenantId : null;
    }

    public function id(): ?int
    {
        return $this->get();
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }

    public function set(int $tenantId): void
    {
        $tenantIdVO = TenantId::from($tenantId);

        if (! $this->tenantRepository->exists($tenantIdVO)) {
            throw new InvalidArgumentException("Organization with ID {$tenantId} does not exist");
        }

        $this->session->put(self::SESSION_KEY, $tenantId);
        $this->auditLogger->logContextSet($tenantIdVO);
    }

    public function switch(int $tenantId, User $user): void
    {
        $tenantIdVO = TenantId::from($tenantId);

        if (! $this->authorizationService->canSwitchTo($tenantIdVO, $user)) {
            if (! $user->isSuperadmin()) {
                throw UnauthorizedTenantSwitchException::superadminRequired($tenantId, $user->id);
            }

            throw UnauthorizedTenantSwitchException::accessDenied($tenantId, $user->id);
        }

        $previousTenantId = $this->get();

        $this->set($tenantId);

        $this->auditLogger->logContextSwitch(
            $user,
            $tenantIdVO,
            $previousTenantId ? TenantId::from($previousTenantId) : null,
            $this->tenantRepository->getName($tenantIdVO)
        );
    }

    public function validate(User $user): bool
    {
        $currentTenantId = $this->get();

        if (! $currentTenantId) {
            return false;
        }

        return $this->authorizationService->canAccessTenant(TenantId::from($currentTenantId), $user);
    }

    public function clear(): void
    {
        $previousTenantId = $this->get();

        $this->session->forget(self::SESSION_KEY);
        $this->auditLogger->logContextCleared(
            $previousTenantId ? TenantId::from($previousTenantId) : null
        );
    }

    public function getDefaultTenant(User $user): ?int
    {
        $tenantId = $this->authorizationService->getDefaultTenant($user);

        return $tenantId?->getValue();
    }

    public function initialize(?User $user = null): void
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $currentTenantId = $this->get();

        if (! $currentTenantId) {
            $defaultTenantId = $this->getDefaultTenant($user);

            if ($defaultTenantId) {
                $this->set($defaultTenantId);
            }

            return;
        }

        if (! $this->validate($user)) {
            $this->clear();

            $defaultTenantId = $this->getDefaultTenant($user);

            if ($defaultTenantId) {
                $this->set($defaultTenantId);
            }

            $this->auditLogger->logInvalidContextReset(
                $user,
                TenantId::from($currentTenantId),
                $defaultTenantId ? TenantId::from($defaultTenantId) : null
            );
        }
    }

    /**
     * Get the current tenant ID from the tenant context.
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->get();
    }

    public function canSwitchTo(int $tenantId, User $user): bool
    {
        try {
            $tenantIdVO = TenantId::from($tenantId);

            if (! $this->tenantRepository->exists($tenantIdVO)) {
                return false;
            }

            return $this->authorizationService->canSwitchTo($tenantIdVO, $user);
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
