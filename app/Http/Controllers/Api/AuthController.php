<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Services\ApiAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Authentication Controller
 * 
 * Handles API token authentication endpoints.
 */
class AuthController extends Controller
{
    public function __construct(
        private readonly ApiAuthenticationService $authService
    ) {}

    /**
     * Authenticate user and return API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->authenticate(
                $request->validated('email'),
                $request->validated('password'),
                $request->validated('token_name', 'api-token')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Authentication successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Revoke current user's tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $this->authService->revokeTokens($user);

        return response()->json([
            'success' => true,
            'message' => 'Tokens revoked successfully',
        ]);
    }

    /**
     * Get current user information.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role']),
                'abilities' => $this->authService->getAbilitiesForRole($user->role),
                'tokens' => $this->authService->getUserTokens($user),
            ],
        ]);
    }

    /**
     * Refresh API token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokenName = $request->input('token_name', 'api-token');

        $newToken = $this->authService->refreshToken($user, $tokenName);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $newToken,
                'expires_at' => now()->addMinutes(config('sanctum.expiration'))->toISOString(),
            ],
            'message' => 'Token refreshed successfully',
        ]);
    }
}