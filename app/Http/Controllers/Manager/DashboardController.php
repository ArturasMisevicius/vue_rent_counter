<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
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
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();

        // Cache statistics for 5 minutes per tenant
        $stats = Cache::remember($cacheKey, 300, function () use ($currentMonthStart, $now) {
            return [
                'total_properties' => Property::count(),
                'active_meters' => Meter::count(),
                'active_tenants' => Tenant::count(),
                'meters_pending_reading' => Meter::whereDoesntHave('readings', function ($query) use ($currentMonthStart) {
                    $query->where('reading_date', '>=', $currentMonthStart);
                })->count(),
                'draft_invoices' => Invoice::draft()->count(),
                'overdue_invoices' => Invoice::where('status', InvoiceStatus::FINALIZED)
                    ->whereDate('due_date', '<', $now)
                    ->count(),
                'recent_invoices' => Invoice::with(['tenant.property'])->latest()->take(5)->get(),
            ];
        });

        // Cache properties needing readings for 5 minutes per tenant
        $propertiesNeedingReadings = Cache::remember("{$cacheKey}_pending_readings", 300, function () use ($currentMonthStart) {
            return Property::whereHas('meters', function ($query) use ($currentMonthStart) {
                $query->whereDoesntHave('readings', function ($q) use ($currentMonthStart) {
                    $q->where('reading_date', '>=', $currentMonthStart);
                });
            })
            ->with(['building', 'meters' => function ($query) use ($currentMonthStart) {
                $query->whereDoesntHave('readings', function ($q) use ($currentMonthStart) {
                    $q->where('reading_date', '>=', $currentMonthStart);
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

        return view('pages.dashboard.manager', compact('stats', 'propertiesNeedingReadings', 'draftInvoices'));
    }
}
