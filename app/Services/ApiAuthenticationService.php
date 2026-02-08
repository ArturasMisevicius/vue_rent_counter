<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API Authentication Service
 * 
 * Handles API token creation, validation, and management
 * with role-based abilities and security controls.
 */
class ApiAuthenticationService
{
    /**
     * Authenticate user and create API token.
     */
    public function authenticate(string $email, string $password, string $tokenName = 'api-token'): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active || $user->suspended_at) {
            throw ValidationException::withMessages([
                'account' => ['Account is inactive or suspended.'],
            ]);
        }

        $token = $user->createApiToken($tokenName);

        return [
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
            'abilities' => $this->getAbilitiesForRole($user->role),
            'expires_at' => now()->addMinutes(config('sanctum.expiration'))->toISOString(),
        ];
    }

    /**
     * Revoke user's API tokens.
     */
    public function revokeTokens(User $user, ?string $tokenName = null): void
    {
        if ($tokenName) {
            $user->tokens()->where('name', $tokenName)->delete();
        } else {
            $user->revokeAllApiTokens();
        }
    }

    /**
     * Get abilities for user role.
     */
    public function getAbilitiesForRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::SUPERADMIN => [
                '*', // All abilities
            ],
            UserRole::ADMIN, UserRole::MANAGER => [
                'meter-reading:read',
                'meter-reading:write',
                'meter-reading:validate',
                'property:read',
                'property:write',
                'invoice:read',
                'invoice:write',
                'validation:read',
                'validation:write',
                'tenant:read',
                'tenant:write',
                'building:read',
                'building:write',
            ],
            UserRole::TENANT => [
                'meter-reading:read',
                'meter-reading:write',
                'validation:read',
                'property:read', // Own property only
                'invoice:read', // Own invoices only
            ],
            default => [],
        };
    }

    /**
     * Validate API request abilities.
     */
    public function validateAbility(Request $request, string $ability): bool
    {
        $user = $request->user();
        
        if (!$user) {
            return false;
        }

        // Superadmin has all abilities
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        return $user->hasApiAbility($ability);
    }

    /**
     * Get user's active tokens with metadata.
     */
    public function getUserTokens(User $user): array
    {
        return $user->tokens()
            ->select(['id', 'name', 'abilities', 'last_used_at', 'created_at'])
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    /**
     * Refresh token (revoke old, create new).
     */
    public function refreshToken(User $user, string $oldTokenName, ?string $newTokenName = null): string
    {
        // Revoke old token
        $this->revokeTokens($user, $oldTokenName);
        
        // Create new token
        return $user->createApiToken($newTokenName ?? $oldTokenName);
    }
}
