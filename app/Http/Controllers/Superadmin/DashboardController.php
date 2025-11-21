<?php

namespace App\Http\Controllers\Superadmin;

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
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $expiredSubscriptions = Subscription::where('status', 'expired')->count();
        $suspendedSubscriptions = Subscription::where('status', 'suspended')->count();
        
        // Get subscription breakdown by plan type
        $subscriptionsByPlan = Subscription::select('plan_type', DB::raw('count(*) as count'))
            ->groupBy('plan_type')
            ->get()
            ->pluck('count', 'plan_type');
        
        // Get expiring subscriptions (within 14 days)
        $expiringSubscriptions = Subscription::where('status', 'active')
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
        
        // Get recent admin activity (last 10 logins)
        $recentActivity = User::withoutGlobalScopes()
            ->where('role', UserRole::ADMIN)
            ->whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
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
            'topOrganizations'
        ));
    }
}
