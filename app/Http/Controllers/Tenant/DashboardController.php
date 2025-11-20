<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        
        if (!$tenant) {
            $stats = [
                'latest_invoice' => null,
                'total_invoices' => 0,
                'unpaid_invoices' => 0,
            ];
            return view('tenant.dashboard', compact('stats', 'tenant'));
        }
        
        $cacheKey = "tenant_dashboard_{$tenant->id}";
        
        // Eager load relationships for tenant
        $tenant->load(['property.meters.readings' => function ($query) {
            $query->latest('reading_date')->limit(1);
        }]);
        
        // Cache statistics for 5 minutes per tenant
        $stats = Cache::remember($cacheKey, 300, function () use ($tenant) {
            return [
                'latest_invoice' => $tenant->invoices()->with('items')->latest()->first(),
                'total_invoices' => $tenant->invoices()->count(),
                'unpaid_invoices' => $tenant->invoices()->where('status', 'finalized')->count(),
            ];
        });

        return view('tenant.dashboard', compact('stats', 'tenant'));
    }
}
