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
}
