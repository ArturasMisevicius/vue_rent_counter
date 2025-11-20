<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $tenant?->property;

        return view('tenant.property.show', compact('property', 'tenant'));
    }

    public function meters(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $tenant?->property;
        $meters = $property?->meters ?? collect();

        return view('tenant.property.meters', compact('meters', 'property'));
    }
}
