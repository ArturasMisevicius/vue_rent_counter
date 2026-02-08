<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MeterReading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        
        if (!$property) {
            $stats = [
                'property' => null,
                'latest_readings' => collect(),
                'unpaid_balance' => 0,
                'total_invoices' => 0,
                'unpaid_invoices' => 0,
            ];
            return view('pages.dashboard.tenant', compact('stats'));
        }
        
        $cacheKey = "tenant_dashboard_{$user->id}";
        
        // Eager load property relationships
        $property->load([
            'meters.readings' => function ($query) {
                $query->latest('reading_date')->limit(2);
            },
            'meters.serviceConfiguration.utilityService',
            'building',
        ]);
        
        // Cache statistics for 5 minutes per user
        $stats = Cache::remember($cacheKey, 300, function () use ($user, $property) {
            // Get tenant record for invoice lookup (legacy compatibility)
            $tenant = $user->tenant;
            
            // Get latest meter readings for assigned property
            $latestReadings = MeterReading::whereHas('meter', function ($query) use ($property) {
                $query->where('property_id', $property->id);
            })
            ->with(['meter.serviceConfiguration.utilityService'])
            ->latest('reading_date')
            ->limit(5)
            ->get();
            
            // Calculate unpaid invoice balance
            $unpaidBalance = 0;
            $totalInvoices = 0;
            $unpaidInvoices = 0;
            
            if ($tenant) {
                $unpaidBalance = Invoice::where('tenant_renter_id', $tenant->id)
                    ->where('status', 'finalized')
                    ->sum('total_amount');
                
                $totalInvoices = Invoice::where('tenant_renter_id', $tenant->id)->count();
                $unpaidInvoices = Invoice::where('tenant_renter_id', $tenant->id)
                    ->where('status', 'finalized')
                    ->count();
            }
            
            // Build per-meter consumption comparisons using last two readings
            $consumptionTrends = $property->meters->map(function ($meter) {
                $readings = $meter->readings->sortByDesc('reading_date')->values();
                $latest = $readings->get(0);
                $previous = $readings->get(1);

                $delta = null;
                $percent = null;

                if ($latest && $previous) {
                    $delta = $latest->value - $previous->value;
                    $percent = $previous->value != 0 ? ($delta / $previous->value) * 100 : null;
                }

                return [
                    'meter' => $meter,
                    'latest' => $latest,
                    'previous' => $previous,
                    'delta' => $delta,
                    'percent' => $percent,
                ];
            });

            return [
                'property' => $property,
                'latest_readings' => $latestReadings,
                'unpaid_balance' => $unpaidBalance,
                'total_invoices' => $totalInvoices,
                'unpaid_invoices' => $unpaidInvoices,
                'consumption_trends' => $consumptionTrends,
            ];
        });

        return view('pages.dashboard.tenant', compact('stats'));
    }
}
