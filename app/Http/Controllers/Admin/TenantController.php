<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use App\Services\AccountManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function __construct(
        protected AccountManagementService $accountManagementService
    ) {}

    /**
     * Display a listing of tenant accounts.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        
        $user = auth()->user();
        
        // Admin users see only their tenants
        $tenants = User::where('role', 'tenant')
            ->where('tenant_id', $user->tenant_id)
            ->with(['property', 'parentUser'])
            ->latest()
            ->paginate(20);

        return view('admin.tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new tenant account.
     */
    public function create()
    {
        $this->authorize('create', User::class);
        
        $user = auth()->user();
        
        // Get properties belonging to this admin
        $properties = Property::where('tenant_id', $user->tenant_id)
            ->orderBy('address')
            ->get();

        return view('admin.tenants.create', compact('properties'));
    }

    /**
     * Store a newly created tenant account.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'property_id' => 'required|exists:properties,id',
        ]);

        try {
            $tenant = $this->accountManagementService->createTenantAccount(
                $validated,
                auth()->user()
            );

            return redirect()->route('admin.tenants.index')
                ->with('success', 'Tenant account created successfully. Welcome email has been sent.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified tenant account.
     */
    public function show(User $tenant)
    {
        $this->authorize('view', $tenant);
        
        // Load relationships
        $tenant->load([
            'property',
            'parentUser',
            'meterReadings' => function ($query) {
                $query->latest('reading_date')->take(10);
            }
        ]);

        // Get assignment history from audit log
        $assignmentHistory = DB::table('user_assignments_audit')
            ->where('user_id', $tenant->id)
            ->whereIn('action', ['created', 'assigned', 'reassigned'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get invoices for tenant's property
        $recentInvoices = DB::table('invoices')
            ->where('property_id', $tenant->property_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.tenants.show', compact('tenant', 'assignmentHistory', 'recentInvoices'));
    }

    /**
     * Show the form for editing the tenant account.
     */
    public function edit(User $tenant)
    {
        $this->authorize('update', $tenant);
        
        $user = auth()->user();
        
        // Get properties belonging to this admin
        $properties = Property::where('tenant_id', $user->tenant_id)
            ->orderBy('address')
            ->get();

        return view('admin.tenants.edit', compact('tenant', 'properties'));
    }

    /**
     * Update the specified tenant account.
     */
    public function update(Request $request, User $tenant)
    {
        $this->authorize('update', $tenant);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $tenant->id,
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant account updated successfully.');
    }

    /**
     * Toggle the active status of the tenant account.
     */
    public function toggleActive(User $tenant)
    {
        $this->authorize('update', $tenant);
        
        if ($tenant->is_active) {
            $this->accountManagementService->deactivateAccount($tenant, 'Deactivated by admin');
            $message = 'Tenant account deactivated successfully.';
        } else {
            $this->accountManagementService->reactivateAccount($tenant);
            $message = 'Tenant account reactivated successfully.';
        }

        return back()->with('success', $message);
    }

    /**
     * Show the form for reassigning tenant to a different property.
     */
    public function reassignForm(User $tenant)
    {
        $this->authorize('update', $tenant);
        
        $user = auth()->user();
        
        // Get properties belonging to this admin (excluding current property)
        $properties = Property::where('tenant_id', $user->tenant_id)
            ->where('id', '!=', $tenant->property_id)
            ->orderBy('address')
            ->get();

        return view('admin.tenants.reassign', compact('tenant', 'properties'));
    }

    /**
     * Reassign tenant to a different property.
     */
    public function reassign(Request $request, User $tenant)
    {
        $this->authorize('update', $tenant);
        
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
        ]);

        try {
            $newProperty = Property::findOrFail($validated['property_id']);
            
            $this->accountManagementService->reassignTenant(
                $tenant,
                $newProperty,
                auth()->user()
            );

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant reassigned successfully. Notification email has been sent.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
