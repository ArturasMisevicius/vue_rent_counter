<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    /**
     * Start impersonating a user.
     *
     * Requirements: 11.1, 11.2
     */
    public function start(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->impersonationService->startImpersonation($user, $request->input('reason'));

            return redirect()->route('dashboard')
                ->with('success', __('app.impersonation.started_successfully', ['user' => $user->name]));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * End impersonation and restore superadmin session.
     *
     * Requirements: 11.4
     */
    public function end()
    {
        try {
            $this->impersonationService->endImpersonation();

            return redirect()->route('superadmin.dashboard')
                ->with('success', __('app.impersonation.ended_successfully'));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display impersonation history.
     *
     * Requirements: 11.5
     */
    public function history(Request $request)
    {
        $query = OrganizationActivityLog::query()
            ->whereIn('action', ['impersonation_started', 'impersonation_ended'])
            ->with(['organization', 'user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('superadmin_id')) {
            $query->where('user_id', $request->input('superadmin_id'));
        }

        if ($request->filled('target_user_id')) {
            $query->where('resource_id', $request->input('target_user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(25);

        // Get superadmins for filter dropdown
        $superadmins = User::where('role', 'superadmin')->get(['id', 'name']);

        // Get target users for filter dropdown (users who have been impersonated)
        $targetUsers = User::whereIn('id',
            OrganizationActivityLog::whereIn('action', ['impersonation_started', 'impersonation_ended'])
                ->distinct()
                ->pluck('resource_id')
        )->get(['id', 'name']);

        return view('pages.impersonation.history', compact('logs', 'superadmins', 'targetUsers'));
    }
}
