<?php

namespace App\Http\Middleware;

use App\Enums\AuditLogAction;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManagerPermission
{
    public function __construct(
        private readonly ManagerPermissionService $managerPermissionService,
        private readonly OrganizationContext $organizationContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->forbidden($request, resource: $resource, action: $action);
        }

        $organization = $this->organizationContext->currentOrganization();

        if ($organization === null) {
            return $user->isManager()
                ? $this->forbidden($request, $user, resource: $resource, action: $action)
                : $next($request);
        }

        if (! $this->managerPermissionService->isManagerForOrganization($user, $organization)) {
            return $next($request);
        }

        if ($this->managerPermissionService->can($user, $organization, $resource, $action)) {
            return $next($request);
        }

        return $this->forbidden($request, $user, $organization, $resource, $action);
    }

    private function forbidden(
        Request $request,
        ?User $user = null,
        ?Organization $organization = null,
        ?string $resource = null,
        ?string $action = null,
    ): JsonResponse|RedirectResponse {
        $message = __('admin.manager_permissions.forbidden');

        if ($user instanceof User && $organization instanceof Organization) {
            $this->auditLogger->record(
                AuditLogAction::REJECTED,
                $organization,
                [
                    'context' => [
                        'mutation' => 'manager.forbidden_access_attempt',
                        'resource' => $resource,
                        'action' => $action,
                        'route' => $request->route()?->getName(),
                    ],
                    'manager' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
                actorUserId: $user->id,
                description: "Forbidden manager access attempt: {$user->email}",
            );
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }

        if (str_starts_with((string) $request->route()?->getName(), 'filament.')) {
            Notification::make()
                ->danger()
                ->title($message)
                ->send();

            return response()->redirectTo(url()->previous() ?: '/');
        }

        abort(Response::HTTP_FORBIDDEN, $message);
    }
}
