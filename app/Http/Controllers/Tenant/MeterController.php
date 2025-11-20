<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Meter;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $tenant?->property;
        
        $meters = $property 
            ? $property->meters()->with(['readings' => function ($query) {
                $query->latest('reading_date')->limit(1);
            }])->paginate(20)
            : collect();

        return view('tenant.meters.index', compact('meters'));
    }

    public function show(Request $request, Meter $meter)
    {
        $user = $request->user();
        $tenant = $user->tenant ?? null;
        $property = $tenant?->property;
        
        if (!$property || $meter->property_id !== $property->id) {
            abort(403);
        }

        // Eager load readings and property for the meter
        $meter->load(['readings' => function ($query) {
            $query->latest('reading_date')->limit(12);
        }, 'property']);
        
        return view('tenant.meters.show', compact('meter'));
    }
}
