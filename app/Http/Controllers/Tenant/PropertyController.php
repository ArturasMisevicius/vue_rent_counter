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
            $property->load(['meters', 'building']);
        }

        return view('tenant.property.show', compact('property'));
    }

    public function meters(Request $request)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        $meters = $property?->meters ?? collect();

        return view('tenant.property.meters', compact('meters', 'property'));
    }
}
