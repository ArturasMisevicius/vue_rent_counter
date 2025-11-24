<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the superadmin dashboard with system-wide statistics.
     * 
     * Requirements: 1.1, 17.1, 17.3
     */
    public function index()
    {
        // Get subscription statistics
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->count();
        $expiredSubscriptions = Subscription::where('status', SubscriptionStatus::EXPIRED->value)->count();
        $suspendedSubscriptions = Subscription::where('status', SubscriptionStatus::SUSPENDED->value)->count();
        
        // Get subscription breakdown by plan type
        $subscriptionsByPlan = Subscription::select('plan_type', DB::raw('count(*) as count'))
            ->groupBy('plan_type')
            ->get()
            ->pluck('count', 'plan_type');
        
        // Get expiring subscriptions (within 14 days)
        $expiringSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE->value)
            ->where('expires_at', '<=', now()->addDays(14))
            ->where('expires_at', '>=', now())
            ->with('user')
            ->get();
        
        // Get total organizations (admin users)
        $totalOrganizations = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->count();
        
        $activeOrganizations = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->where('is_active', true)
            ->count();
        
        // Get system-wide usage metrics
        $totalProperties = Property::withoutGlobalScopes()->count();
        $totalBuildings = Building::withoutGlobalScopes()->count();
        $totalTenants = User::withoutGlobalScopes()
            ->where('role', UserRole::TENANT)
            ->count();
        $totalInvoices = Invoice::withoutGlobalScopes()->count();
        
        // Get recent admin activity (last 10 created/updated)
        $recentActivity = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();
        
        // Get top organizations by property count
        $topOrganizations = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->withCount(['properties' => function ($query) {
                $query->withoutGlobalScopes();
            }])
            ->orderBy('properties_count', 'desc')
            ->take(5)
            ->get();

        // Dashboard data tables
        $subscriptionList = Subscription::with('user')
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        $organizationList = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->with('subscription')
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        $organizationLookup = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->get(['id', 'tenant_id', 'organization_name', 'email'])
            ->keyBy('tenant_id');

        $latestProperties = Property::withoutGlobalScopes()
            ->with(['building'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestBuildings = Building::withoutGlobalScopes()
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestTenants = User::withoutGlobalScopes()
            ->where('role', UserRole::TENANT)
            ->with(['property'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $latestInvoices = Invoice::withoutGlobalScopes()
            ->with(['tenant', 'property'])
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
        
        return view('superadmin.dashboard', compact(
            'totalSubscriptions',
            'activeSubscriptions',
            'expiredSubscriptions',
            'suspendedSubscriptions',
            'subscriptionsByPlan',
            'expiringSubscriptions',
            'totalOrganizations',
            'activeOrganizations',
            'totalProperties',
            'totalBuildings',
            'totalTenants',
            'totalInvoices',
            'recentActivity',
            'topOrganizations',
            'subscriptionList',
            'organizationList',
            'organizationLookup',
            'latestProperties',
            'latestBuildings',
            'latestTenants',
            'latestInvoices'
        ));
    }
}
