<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(): View
    {
        $buildings = Building::withCount('properties')
            ->with(['properties' => fn ($query) => $query->withCount(['tenants', 'meters'])->orderBy('address')])
            ->orderBy('name')
            ->get();

        return view('pages.buildings.index', compact('buildings'));
    }

    public function show(Building $building): View
    {
        $building->load([
            'properties' => function ($query) {
                $query->withCount(['tenants', 'meters'])
                    ->with([
                        'tenants.invoices' => fn ($q) => $q->latest()->limit(5),
                        'meters.readings' => fn ($q) => $q->latest('reading_date')->limit(1),
                    ])
                    ->orderBy('address');
            },
        ]);

        $properties = $building->properties;
        $meters = $properties->flatMap->meters;
        $tenants = $properties->flatMap->tenants->unique('id');
        $invoices = $tenants->flatMap->invoices->unique('id');

        return view('pages.buildings.show', compact(
            'building',
            'properties',
            'meters',
            'tenants',
            'invoices'
        ));
    }
}
