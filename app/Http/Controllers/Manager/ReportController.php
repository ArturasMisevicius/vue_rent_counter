<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports index with available report options.
     */
    public function index(): View
    {
        return view('manager.reports.index');
    }

    /**
     * Generate consumption report by property.
     */
    public function consumption(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'property_id' => ['nullable', 'exists:properties,id'],
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $query = MeterReading::with(['meter.property'])
            ->whereBetween('reading_date', [$startDate, $endDate]);

        if (isset($validated['property_id'])) {
            $query->whereHas('meter', function ($q) use ($validated) {
                $q->where('property_id', $validated['property_id']);
            });
        }

        $readings = $query->get()->groupBy('meter.property.address');
        $properties = Property::all();

        return view('manager.reports.consumption', compact('readings', 'startDate', 'endDate', 'properties'));
    }

    /**
     * Generate revenue report by period.
     */
    public function revenue(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $invoices = Invoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->with('tenant.property')
            ->get();

        $totalRevenue = $invoices->sum('total_amount');
        $paidRevenue = $invoices->where('status->value', 'paid')->sum('total_amount');
        $finalizedRevenue = $invoices->where('status->value', 'finalized')->sum('total_amount');
        $draftRevenue = $invoices->where('status->value', 'draft')->sum('total_amount');

        return view('manager.reports.revenue', compact(
            'invoices',
            'totalRevenue',
            'paidRevenue',
            'finalizedRevenue',
            'draftRevenue',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Generate meter reading compliance report.
     */
    public function meterReadingCompliance(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        // Get all properties with their meters
        $properties = Property::with(['meters' => function ($query) use ($startDate, $endDate) {
            $query->with(['readings' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('reading_date', [$startDate, $endDate]);
            }]);
        }])->get();

        // Calculate compliance
        $propertiesWithReadings = $properties->filter(function ($property) {
            return $property->meters->every(function ($meter) {
                return $meter->readings->isNotEmpty();
            });
        });

        $complianceRate = $properties->count() > 0 
            ? ($propertiesWithReadings->count() / $properties->count()) * 100 
            : 0;

        return view('manager.reports.meter-reading-compliance', compact(
            'properties',
            'propertiesWithReadings',
            'complianceRate',
            'month'
        ));
    }
}
