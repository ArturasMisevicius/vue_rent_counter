<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::withoutGlobalScopes()
            ->with([
                'property:id,address,building_id,tenant_id',
                'property.building:id,name,address',
            ])
            ->withCount([
                'invoices',
                'meterReadings',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'property.building',
            'invoices' => fn ($q) => $q->latest('billing_period_start'),
            'meterReadings' => fn ($q) => $q->latest('reading_date')->limit(10),
        ]);

        return view('pages.tenants.show', compact('tenant'));
    }
}
