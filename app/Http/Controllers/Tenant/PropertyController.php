<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        
        // Eager load relationships
        if ($property) {
            $property->load([
                'building',
                'meters.serviceConfiguration.utilityService',
                'serviceConfigurations' => fn ($query) => $query
                    ->active()
                    ->effectiveOn(now())
                    ->with(['utilityService', 'meters']),
            ]);
        }

        return view('pages.property.show-tenant', compact('property'));
    }

    public function meters(Request $request)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        $meters = $property
            ? $property->meters()->with('serviceConfiguration.utilityService')->get()
            : collect();

        return view('pages.property.meters-tenant', compact('meters', 'property'));
    }
}
