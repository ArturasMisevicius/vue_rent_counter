<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        
        $user = auth()->user();
        
        // For admin role, show portfolio-specific statistics
        if ($user->role->value === 'admin') {
            return $this->adminDashboard($user);
        }
        
        // For other roles (superadmin, manager), show system-wide statistics
        return $this->systemWideDashboard();
    }
    
    /**
     * Admin-specific dashboard with portfolio statistics and subscription info.
     */
    protected function adminDashboard(User $admin)
    {
        // Get subscription information
        $subscription = $admin->subscription;
        
        // Calculate subscription status
        $subscriptionStatus = null;
        $daysUntilExpiry = null;
        $showExpiryWarning = false;
        
        if ($subscription) {
            $daysUntilExpiry = $subscription->daysUntilExpiry();
            $showExpiryWarning = $daysUntilExpiry <= 14 && $daysUntilExpiry > 0;
            
            if ($subscription->isExpired()) {
                $subscriptionStatus = 'expired';
            } elseif ($showExpiryWarning) {
                $subscriptionStatus = 'expiring_soon';
            } else {
                $subscriptionStatus = 'active';
            }
        }
        
        // Portfolio statistics (scoped to admin's tenant_id)
        $stats = [
            'total_properties' => Property::where('tenant_id', $admin->tenant_id)->count(),
            'total_buildings' => Building::where('tenant_id', $admin->tenant_id)->count(),
            'total_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', 'tenant')
                ->count(),
            'active_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', 'tenant')
                ->where('is_active', true)
                ->count(),
            'active_meters' => Meter::whereHas('property', function ($query) use ($admin) {
                $query->where('tenant_id', $admin->tenant_id);
            })->count(),
            'draft_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->where('status', 'draft')
                ->count(),
            'unpaid_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->whereIn('status', ['draft', 'finalized'])
                ->count(),
            'recent_readings_count' => MeterReading::whereHas('meter.property', function ($query) use ($admin) {
                $query->where('tenant_id', $admin->tenant_id);
            })->where('reading_date', '>=', now()->subDays(7))->count(),
        ];
        
        // Usage against subscription limits
        $usageStats = null;
        if ($subscription) {
            $usageStats = [
                'properties_used' => $stats['total_properties'],
                'properties_max' => $subscription->max_properties,
                'properties_percentage' => $subscription->max_properties > 0 
                    ? round(($stats['total_properties'] / $subscription->max_properties) * 100) 
                    : 0,
                'tenants_used' => $stats['total_tenants'],
                'tenants_max' => $subscription->max_tenants,
                'tenants_percentage' => $subscription->max_tenants > 0 
                    ? round(($stats['total_tenants'] / $subscription->max_tenants) * 100) 
                    : 0,
            ];
        }
        
        // Pending tasks
        $pendingTasks = [
            'pending_meter_readings' => $this->getPendingMeterReadingsCount($admin),
            'draft_invoices' => $stats['draft_invoices'],
            'inactive_tenants' => $stats['total_tenants'] - $stats['active_tenants'],
        ];
        
        // Recent activity (scoped to admin's tenant_id)
        $recentActivity = [
            'recent_tenants' => User::where('tenant_id', $admin->tenant_id)
                ->where('role', 'tenant')
                ->with('property')
                ->latest()
                ->take(5)
                ->get(),
            'recent_invoices' => Invoice::where('tenant_id', $admin->tenant_id)
                ->with(['property'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_readings' => MeterReading::whereHas('meter.property', function ($query) use ($admin) {
                $query->where('tenant_id', $admin->tenant_id);
            })
                ->with(['meter.property', 'enteredBy'])
                ->latest('reading_date')
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard', compact(
            'stats',
            'subscription',
            'subscriptionStatus',
            'daysUntilExpiry',
            'showExpiryWarning',
            'usageStats',
            'pendingTasks',
            'recentActivity'
        ));
    }
    
    /**
     * System-wide dashboard for superadmin and manager roles.
     */
    protected function systemWideDashboard()
    {
        // Cache system-wide statistics for 5 minutes
        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'total_users' => User::count(),
                'admin_count' => User::where('role', 'admin')->count(),
                'manager_count' => User::where('role', 'manager')->count(),
                'tenant_count' => User::where('role', 'tenant')->count(),
                'total_properties' => Property::count(),
                'total_buildings' => Building::count(),
                'active_meters' => Meter::count(),
                'total_providers' => Provider::count(),
                'active_tariffs' => Tariff::where('effective_date', '<=', now())
                    ->whereNull('end_date')
                    ->count(),
                'draft_invoices' => Invoice::draft()->count(),
                'finalized_invoices' => Invoice::finalized()->count(),
                'paid_invoices' => Invoice::paid()->count(),
                'total_meter_readings' => MeterReading::count(),
                'recent_readings_count' => MeterReading::where('reading_date', '>=', now()->subDays(7))->count(),
            ];
        });
        
        // Cache recent system activity for 5 minutes
        $recentActivity = Cache::remember('admin_dashboard_activity', 300, function () {
            return [
                'recent_users' => User::with('tenant')->latest()->take(5)->get(),
                'recent_invoices' => Invoice::with(['tenant.property'])->latest()->take(5)->get(),
                'recent_readings' => MeterReading::with(['meter.property', 'enteredBy'])
                    ->latest('reading_date')
                    ->take(5)
                    ->get(),
            ];
        });

        return view('admin.dashboard', compact('stats', 'recentActivity'));
    }
    
    /**
     * Get count of properties that need meter readings this month.
     */
    protected function getPendingMeterReadingsCount(User $admin): int
    {
        $startOfMonth = now()->startOfMonth();
        
        return Property::where('tenant_id', $admin->tenant_id)
            ->whereHas('meters', function ($query) use ($startOfMonth) {
                $query->whereDoesntHave('readings', function ($q) use ($startOfMonth) {
                    $q->where('reading_date', '>=', $startOfMonth);
                });
            })
            ->count();
    }
}
