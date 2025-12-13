<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
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

        $tenantId = $organization->tenant_id;

        // Load relationships without global scopes
        $organization->load([
            'subscription',
            'childUsers' => function ($query) {
                $query->withoutGlobalScopes();
            }
        ]);

        // Get organization statistics
        $stats = [
            'total_properties' => Property::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'total_buildings' => Building::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'total_tenants' => $organization->childUsers()->count(),
            'total_invoices' => Invoice::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'active_tenants' => $organization->childUsers()->where('is_active', true)->count(),
        ];

        $occupiedPropertiesCount = Property::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereHas('tenants')
            ->count();

        $relationshipMetrics = [
            'occupied_properties' => $occupiedPropertiesCount,
            'vacant_properties' => max($stats['total_properties'] - $occupiedPropertiesCount, 0),
            'metered_properties' => Property::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereHas('meters')
                ->count(),
            'draft_invoices' => Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->draft()
                ->count(),
            'finalized_invoices' => Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->finalized()
                ->count(),
            'paid_invoices' => Invoice::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->paid()
                ->count(),
        ];

        $properties = Property::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with([
                'building:id,name,address',
                'tenants' => fn ($query) => $query
                    ->select(
                        'tenants.id',
                        'tenants.tenant_id',
                        'tenants.name',
                        'tenants.email',
                        'tenants.phone',
                        'tenants.property_id',
                        'tenants.lease_start'
                    )
                    ->orderBy('tenants.lease_start', 'desc'),
            ])
            ->withCount([
                'meters',
                'tenantAssignments as tenant_history_count',
            ])
            ->orderBy('address')
            ->paginate(10, ['*'], 'properties_page');

        $buildings = Building::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->withCount([
                'properties',
                'properties as occupied_units_count' => fn ($query) => $query->whereHas('tenants'),
            ])
            ->with([
                'properties' => fn ($query) => $query
                    ->select('id', 'building_id', 'address')
                    ->withCount('tenants')
                    ->orderBy('address')
                    ->limit(3),
            ])
            ->orderBy('name')
            ->paginate(10, ['*'], 'buildings_page');

        $invoiceCountsByProperty = Invoice::withoutGlobalScopes()
            ->selectRaw('tenants.property_id as property_id, COUNT(*) as aggregate')
            ->join('tenants', 'tenants.id', '=', 'invoices.tenant_renter_id')
            ->where('invoices.tenant_id', $tenantId)
            ->groupBy('tenants.property_id')
            ->pluck('aggregate', 'property_id');

        $latestInvoicesByProperty = Invoice::withoutGlobalScopes()
            ->select(
                'invoices.id',
                'invoices.invoice_number',
                'invoices.status',
                'invoices.total_amount',
                'invoices.billing_period_end',
                'tenants.property_id'
            )
            ->join('tenants', 'tenants.id', '=', 'invoices.tenant_renter_id')
            ->where('invoices.tenant_id', $tenantId)
            ->orderByDesc('invoices.billing_period_end')
            ->get()
            ->unique('property_id')
            ->keyBy('property_id');

        $invoices = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with([
                'tenant' => fn ($query) => $query->select('id', 'tenant_id', 'name', 'email', 'property_id'),
                'tenant.property' => fn ($query) => $query->select('id', 'tenant_id', 'address', 'building_id'),
                'tenant.property.building' => fn ($query) => $query->select('id', 'tenant_id', 'name', 'address'),
            ])
            ->orderByDesc('billing_period_start')
            ->paginate(10, ['*'], 'invoices_page');

        // Get all tenants for this organization
        $tenants = $organization->childUsers()
            ->with('property')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('superadmin.organizations.show', compact(
            'organization',
            'stats',
            'tenants',
            'properties',
            'buildings',
            'invoices',
            'invoiceCountsByProperty',
            'latestInvoicesByProperty',
            'relationshipMetrics'
        ));
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
