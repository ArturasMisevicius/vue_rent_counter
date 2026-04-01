<?php

namespace App\Http\Middleware;

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Admin\OrganizationContext;
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
    ) {}

    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->forbidden($request);
        }

        $organization = $this->organizationContext->currentOrganization();

        if ($organization === null) {
            return $user->isManager()
                ? $this->forbidden($request)
                : $next($request);
        }

        if (! $this->managerPermissionService->isManagerForOrganization($user, $organization)) {
            return $next($request);
        }

        if ($this->managerPermissionService->can($user, $organization, $resource, $action)) {
            return $next($request);
        }

        return $this->forbidden($request);
    }

    private function forbidden(Request $request): JsonResponse|RedirectResponse
    {
        $message = __('admin.manager_permissions.forbidden');

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
