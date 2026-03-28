<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Auth\ImpersonationManager;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ImpersonationService
{
    public function __construct(
        protected ImpersonationManager $impersonationManager,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @return array<string, int|string|null>|null
     */
    public function current(Request $request): ?array
    {
        return $this->impersonationManager->current($request);
    }

    public function expireIfNecessary(Request $request): ?User
    {
        if (! $this->impersonationManager->hasExpired($request)) {
            return null;
        }

        return $this->stop($request);
    }

    public function stop(Request $request): ?User
    {
        $impersonator = $this->impersonationManager->resolveImpersonator($request);

        $this->impersonationManager->forget($request);

        if ($impersonator !== null) {
            Auth::guard('web')->login($impersonator);
        }

        return $impersonator;
    }

    public function start(User $impersonator, User $target, ?Request $request = null): void
    {
        $request ??= request();

        if ($target->organization !== null && ! $target->organization->status->permitsAccess()) {
            throw new AccessDeniedHttpException(__('superadmin.organizations.messages.cannot_impersonate_suspended'));
        }

        if ($target->organization !== null && $target->organization->hasActiveSecurityIncident()) {
            throw new AccessDeniedHttpException(__('superadmin.organizations.messages.cannot_impersonate_during_security_incident'));
        }

        $this->impersonationManager->start($request, $impersonator, $target);

        Auth::guard('web')->login($target);

        $this->auditLogger->record(
            AuditLogAction::IMPERSONATED,
            $target,
            [
                'context' => [
                    'mutation' => 'impersonation.started',
                ],
            ],
            $impersonator->id,
            'User impersonated',
        );
    }
}
