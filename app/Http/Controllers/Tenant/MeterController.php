<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Meter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MeterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        
        // Verify property_id filtering is applied
        $meters = $property 
            ? $property->meters()->with(['readings' => function ($query) {
                $query->latest('reading_date')->limit(1);
            }, 'serviceConfiguration.utilityService'])->paginate(20)
            : collect();

        $metersCollection = $meters instanceof LengthAwarePaginator ? $meters->getCollection() : $meters;
        $latestReadingDate = $this->resolveLatestReadingDate($metersCollection);
        $meterStyleMap = $this->buildMeterStyleMap($metersCollection);

        return view('tenant.meters.index', compact('meters', 'metersCollection', 'latestReadingDate', 'meterStyleMap'));
    }

    public function show(Request $request, Meter $meter)
    {
        $user = $request->user();
        
        // Get assigned property from hierarchical user model
        $property = $user->property;
        
        // Verify property_id filtering - tenant can only view meters for their assigned property
        if (!$property || $meter->property_id !== $property->id) {
            abort(403, 'You do not have permission to view this meter.');
        }

        // Eager load readings and property for the meter
        $meter->load(['readings' => function ($query) {
            $query->latest('reading_date')->limit(12);
        }, 'property', 'serviceConfiguration.utilityService']);
        
        return view('tenant.meters.show', compact('meter'));
    }

    private function resolveLatestReadingDate(Collection $metersCollection): mixed
    {
        return $metersCollection
            ->flatMap(fn (Meter $meter) => $meter->readings)
            ->filter()
            ->pluck('reading_date')
            ->filter()
            ->sortDesc()
            ->first();
    }

    private function buildMeterStyleMap(Collection $metersCollection): array
    {
        $stylePalettes = [
            ['chip' => 'bg-indigo-100 text-indigo-800', 'halo' => 'from-indigo-200/70 via-white to-white'],
            ['chip' => 'bg-sky-100 text-sky-800', 'halo' => 'from-sky-200/80 via-white to-white'],
            ['chip' => 'bg-emerald-100 text-emerald-800', 'halo' => 'from-emerald-200/75 via-white to-white'],
            ['chip' => 'bg-amber-100 text-amber-800', 'halo' => 'from-amber-200/70 via-white to-white'],
            ['chip' => 'bg-rose-100 text-rose-800', 'halo' => 'from-rose-200/80 via-white to-white'],
            ['chip' => 'bg-violet-100 text-violet-800', 'halo' => 'from-violet-200/75 via-white to-white'],
        ];

        return $metersCollection->mapWithKeys(function (Meter $meter) use ($stylePalettes): array {
            $serviceId = $meter->serviceConfiguration?->utilityService?->id;
            $seed = is_int($serviceId) ? $serviceId : crc32((string) $meter->serial_number);
            $index = abs((int) $seed) % count($stylePalettes);

            return [$meter->id => $stylePalettes[$index]];
        })->all();
    }
}
