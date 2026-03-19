<?php

declare(strict_types=1);

namespace App\Filament\Support\Workspace;

use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class WorkspaceResolver
{
    private const REQUEST_ATTRIBUTE = '_tenanto_workspace_context';

    public function current(): ?WorkspaceContext
    {
        $request = request();

        if ($request instanceof Request) {
            return $this->resolveForRequest($request);
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return $this->resolveFor($user);
    }

    public function resolveForRequest(Request $request): ?WorkspaceContext
    {
        $cachedContext = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        if ($cachedContext instanceof WorkspaceContext) {
            return $cachedContext;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $workspace = $this->resolveFor($user);

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $workspace);

        return $workspace;
    }

    public function resolveFor(User $user): WorkspaceContext
    {
        if ($user->isSuperadmin()) {
            return new WorkspaceContext(
                userId: $user->id,
                role: $user->role,
                organizationId: null,
                propertyId: null,
            );
        }

        return new WorkspaceContext(
            userId: $user->id,
            role: $user->role,
            organizationId: $user->organization_id,
            propertyId: $user->isTenant() ? $this->resolveTenantPropertyId($user) : null,
        );
    }

    public function hasValidOrganization(User $user): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin() && $user->organization_id === null) {
            return true;
        }

        return $this->resolveFor($user)->organizationId !== null;
    }

    private function resolveTenantPropertyId(User $tenant): ?int
    {
        if ($tenant->organization_id === null) {
            return null;
        }

        $assignment = $tenant->relationLoaded('currentPropertyAssignment')
            ? $tenant->currentPropertyAssignment
            : $tenant->currentPropertyAssignment()
                ->select([
                    'id',
                    'organization_id',
                    'property_id',
                    'tenant_user_id',
                    'assigned_at',
                    'unassigned_at',
                ])
                ->latestAssignedFirst()
                ->first();

        if (! $assignment instanceof PropertyAssignment) {
            return null;
        }

        if ($assignment->organization_id !== $tenant->organization_id) {
            return null;
        }

        $property = $assignment->relationLoaded('property')
            ? $assignment->property
            : $assignment->property()
                ->select(['id', 'organization_id'])
                ->first();

        if (! $property instanceof Property) {
            return null;
        }

        if ($property->organization_id !== $tenant->organization_id) {
            return null;
        }

        return $assignment->property_id;
    }
}
