<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\User;
use App\Services\AccountManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function __construct(
        private AccountManagementService $accountService
    ) {}

    /**
     * Display a listing of all organizations (admin accounts).
     * 
     * Requirements: 1.2, 1.3, 17.5
     */
    public function index(Request $request)
    {
        $query = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->with('subscription');
        
        // Filter by status if provided
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter by subscription status if provided
        if ($request->filled('subscription_status') && in_array($request->subscription_status, SubscriptionStatus::values(), true)) {
            $query->whereHas('subscription', function ($q) use ($request) {
                $q->where('status', $request->subscription_status);
            });
        }
        
        // Search by organization name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('organization_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        $organizations = $query->paginate(20);
        
        return view('superadmin.organizations.index', compact('organizations'));
    }

    /**
     * Show the form for creating a new organization.
     * 
     * Requirements: 2.1, 2.2, 2.3
     */
    public function create()
    {
        return view('superadmin.organizations.create');
    }

    /**
     * Store a newly created organization.
     * 
     * Requirements: 2.1, 2.2, 2.3
     */
    public function store(StoreOrganizationRequest $request)
    {
        $validated = $request->validated();
        
        $superadmin = Auth::user();
        
        $admin = $this->accountService->createAdminAccount($validated, $superadmin);
        
        return redirect()
            ->route('superadmin.organizations.show', $admin)
            ->with('success', __('notifications.organization.created'));
    }

    /**
     * Display the specified organization.
     * 
     * Requirements: 1.2, 1.3, 17.5
     */
    public function show(User $organization)
    {
        // Ensure we're viewing an admin user
        if ($organization->role !== UserRole::ADMIN) {
            abort(404);
        }
        
        // Load relationships without global scopes
        $organization->load([
            'subscription',
            'childUsers' => function ($query) {
                $query->withoutGlobalScopes();
            }
        ]);
        
        // Get organization statistics
        $stats = [
            'total_properties' => $organization->properties()->withoutGlobalScopes()->count(),
            'total_buildings' => $organization->buildings()->withoutGlobalScopes()->count(),
            'total_tenants' => $organization->childUsers()->count(),
            'total_invoices' => $organization->invoices()->withoutGlobalScopes()->count(),
            'active_tenants' => $organization->childUsers()->where('is_active', true)->count(),
        ];
        
        // Get recent activity
        $recentTenants = $organization->childUsers()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        return view('superadmin.organizations.show', compact('organization', 'stats', 'recentTenants'));
    }

    /**
     * Show the form for editing the specified organization.
     */
    public function edit(User $organization)
    {
        // Ensure we're editing an admin user
        if ($organization->role !== UserRole::ADMIN) {
            abort(404);
        }
        
        $organization->load('subscription');
        
        return view('superadmin.organizations.edit', compact('organization'));
    }

    /**
     * Update the specified organization.
     */
    public function update(UpdateOrganizationRequest $request, User $organization)
    {
        // Ensure we're updating an admin user
        if ($organization->role !== UserRole::ADMIN) {
            abort(404);
        }
        
        $validated = $request->validated();
        
        $organization->update($validated);
        
        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', __('notifications.organization.updated'));
    }

    /**
     * Deactivate the specified organization.
     */
    public function deactivate(User $organization)
    {
        // Ensure we're deactivating an admin user
        if ($organization->role !== UserRole::ADMIN) {
            abort(404);
        }
        
        $this->accountService->deactivateAccount($organization, 'Deactivated by superadmin');
        
        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', __('notifications.organization.deactivated'));
    }

    /**
     * Reactivate the specified organization.
     */
    public function reactivate(User $organization)
    {
        // Ensure we're reactivating an admin user
        if ($organization->role !== UserRole::ADMIN) {
            abort(404);
        }
        
        $this->accountService->reactivateAccount($organization);
        
        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', __('notifications.organization.reactivated'));
    }
}
