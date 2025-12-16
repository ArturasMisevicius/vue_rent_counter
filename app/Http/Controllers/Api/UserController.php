<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Contracts\UserRepositoryInterface;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\Criteria\ActiveUsers;
use App\Repositories\Criteria\DateRange;
use App\Repositories\Criteria\SearchTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * User API Controller
 * 
 * Demonstrates repository pattern usage in controllers with:
 * - Repository injection via constructor
 * - Error handling patterns
 * - Response formatting
 * - Criteria usage for complex queries
 */
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     * 
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Display a listing of users.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $search = $request->string('search');
            $role = $request->string('role');
            $active = $request->boolean('active');

            // Start with base query
            $query = $this->userRepository->fresh();

            // Apply search criteria if provided
            if ($search->isNotEmpty()) {
                $searchCriteria = SearchTerm::forUsers($search->toString());
                $query = $query->where(function ($q) use ($searchCriteria) {
                    return $searchCriteria->apply($q);
                });
            }

            // Apply role filter if provided
            if ($role->isNotEmpty() && UserRole::tryFrom($role->toString())) {
                $query = $query->where('role', UserRole::from($role->toString()));
            }

            // Apply active filter if provided
            if ($active) {
                $activeCriteria = new ActiveUsers();
                $query = $activeCriteria->apply($query->getModel()->newQuery());
            }

            // Get paginated results with relationships
            $users = $query->with(['property', 'subscription'])->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Users retrieved successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created user.
     * 
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userRepository->create($request->validated());

            return response()->json([
                'success' => true,
                'data' => $user->load(['property', 'subscription']),
                'message' => 'User created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified user.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository
                ->with(['property', 'subscription', 'childUsers'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User retrieved successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified user.
     * 
     * @param UpdateUserRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $user->load(['property', 'subscription']),
                'message' => 'User updated successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified user.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user statistics.
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->userRepository->getUserStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'User statistics retrieved successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user statistics',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Activate a user account.
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->activateUser($id);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User activated successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Deactivate a user account.
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        try {
            $reason = $request->string('reason')->toString();
            $user = $this->userRepository->deactivateUser($id, $reason ?: null);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User deactivated successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate user',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get users by role.
     * 
     * @param string $role
     * @return JsonResponse
     */
    public function byRole(string $role): JsonResponse
    {
        try {
            $userRole = UserRole::tryFrom($role);
            
            if (!$userRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role specified',
                ], Response::HTTP_BAD_REQUEST);
            }

            $users = $this->userRepository->findByRole($userRole);

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => "Users with role '{$role}' retrieved successfully",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users by role',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search users with advanced criteria.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $this->userRepository->fresh();

            // Apply search term if provided
            if ($request->filled('q')) {
                $searchCriteria = SearchTerm::forUsers($request->string('q')->toString());
                $query = $searchCriteria->apply($query->getModel()->newQuery());
            }

            // Apply date range if provided
            if ($request->filled('created_from') && $request->filled('created_to')) {
                $dateRange = DateRange::createdBetween(
                    new \DateTime($request->string('created_from')->toString()),
                    new \DateTime($request->string('created_to')->toString())
                );
                $query = $dateRange->apply($query);
            }

            // Apply active filter
            if ($request->boolean('active_only')) {
                $activeCriteria = new ActiveUsers();
                $query = $activeCriteria->apply($query);
            }

            $users = $query->with(['property', 'subscription'])->get();

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Search completed successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}