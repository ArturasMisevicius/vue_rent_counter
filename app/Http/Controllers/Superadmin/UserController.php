<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    public function show(User $user)
    {
        $organization = $user->tenant_id
            ? Organization::find($user->tenant_id)
            : null;

        return view('superadmin.users.show', compact('user', 'organization'));
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::random(16);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Password reset.');
    }

    public function deactivate(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'User deactivated.');
    }

    public function reactivate(User $user)
    {
        $user->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'User reactivated.');
    }

    public function impersonate(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $actingUser = $request->user();

        if ($actingUser && $user->id === $actingUser->id) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'target_user' => ['Cannot impersonate yourself.'],
                ],
            ], 422);
        }

        if ($user->isSuperadmin()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'target_user' => ['Cannot impersonate another superadmin.'],
                ],
            ], 422);
        }

        $this->impersonationService->startImpersonation($user, $validated['reason'] ?? null);

        return redirect('/admin/dashboard');
    }

    public function bulkDeactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        User::whereIn('id', $validated['user_ids'])->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $validated['reason'],
        ]);

        return response()->json(['success' => true]);
    }

    public function bulkReactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        User::whereIn('id', $validated['user_ids'])->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return response()->json(['success' => true]);
    }
}
