<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the manager dashboard with property statistics and pending tasks.
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $cacheKey = "manager_dashboard_stats_{$tenantId}";
        
        // Cache statistics for 5 minutes per tenant
        $stats = Cache::remember($cacheKey, 300, function () {
            return [
                'total_properties' => Property::count(),
                'meters_pending_reading' => Meter::whereDoesntHave('readings', function ($query) {
                    $query->where('reading_date', '>=', Carbon::now()->startOfMonth());
                })->count(),
                'draft_invoices' => Invoice::draft()->count(),
                'recent_invoices' => Invoice::with(['tenant.property'])->latest()->take(5)->get(),
            ];
        });

        // Cache properties needing readings for 5 minutes per tenant
        $propertiesNeedingReadings = Cache::remember("{$cacheKey}_pending_readings", 300, function () {
            return Property::whereHas('meters', function ($query) {
                $query->whereDoesntHave('readings', function ($q) {
                    $q->where('reading_date', '>=', Carbon::now()->startOfMonth());
                });
            })
            ->with(['building', 'meters' => function ($query) {
                $query->whereDoesntHave('readings', function ($q) {
                    $q->where('reading_date', '>=', Carbon::now()->startOfMonth());
                });
            }])
            ->limit(10)
            ->get();
        });

        // Cache draft invoices for 5 minutes per tenant
        $draftInvoices = Cache::remember("{$cacheKey}_draft_invoices", 300, function () {
            return Invoice::draft()
                ->with(['tenant.property', 'items'])
                ->latest()
                ->limit(5)
                ->get();
        });

        return view('manager.dashboard', compact('stats', 'propertiesNeedingReadings', 'draftInvoices'));
    }
}
