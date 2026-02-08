<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function index(): View
    {
        $properties = Property::with(['building'])
            ->withCount(['tenants', 'meters'])
            ->orderBy('address')
            ->get();

        return view('superadmin.properties.index', compact('properties'));
    }

    public function show(Property $property): View
    {
        $property->load([
            'building',
            'tenants.invoices' => fn ($q) => $q->latest()->limit(5),
            'tenants' => fn ($q) => $q->withPivot(['assigned_at', 'vacated_at']),
            'meters.readings' => fn ($q) => $q->latest('reading_date')->limit(1),
        ])->loadCount(['tenants', 'meters']);

        $tenants = $property->tenants;
        $invoices = $tenants->flatMap->invoices->unique('id');
        $meters = $property->meters;

        return view('superadmin.properties.show', compact(
            'property',
            'tenants',
            'invoices',
            'meters'
        ));
    }
}
