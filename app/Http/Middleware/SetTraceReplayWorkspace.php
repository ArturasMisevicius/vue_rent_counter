<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TraceReplay\Facades\TraceReplay;

class SetTraceReplayWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            TraceReplay::context([
                'organization_id' => $user->organization_id,
                'user_role' => $user->role?->value,
            ]);

            if ($user->organization_id !== null) {
                TraceReplay::setWorkspaceId($this->workspaceIdForOrganization((int) $user->organization_id));
            }
        }

        return $next($request);
    }

    private function workspaceIdForOrganization(int $organizationId): string
    {
        $hash = md5('tenanto-organization:'.$organizationId);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }
}
