<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerConsumptionReportRequest;
use App\Http\Requests\ManagerMeterComplianceRequest;
use App\Http\Requests\ManagerRevenueReportRequest;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display the reports index with available report options.
     */
    public function index(): View
    {
        // Quick stats for dashboard
        $stats = [
            'total_properties' => Property::count(),
            'total_meters' => Meter::count(),
            'readings_this_month' => MeterReading::whereMonth('reading_date', Carbon::now()->month)->count(),
            'invoices_this_month' => Invoice::whereMonth('billing_period_start', Carbon::now()->month)->count(),
        ];

        return view('manager.reports.index', compact('stats'));
    }

    /**
     * Generate consumption report by property.
     */
    public function consumption(ManagerConsumptionReportRequest $request): View
    {
        $validated = $request->validated();

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $buildingId = $validated['building_id'] ?? null;
        $serviceFilter = $validated['service'] ?? null;

        if (!$serviceFilter && !empty($validated['meter_type'])) {
            $serviceFilter = 'type:' . (string) $validated['meter_type'];
        }

        $query = MeterReading::with([
            'meter.property.building',
            'meter.serviceConfiguration.utilityService',
        ])
            ->whereBetween('reading_date', [$startDate, $endDate]);

        if (isset($validated['property_id'])) {
            $query->whereHas('meter', function ($q) use ($validated) {
                $q->where('property_id', $validated['property_id']);
            });
        }

        if ($serviceFilter) {
            $query->whereHas('meter', function ($q) use ($serviceFilter) {
                [$kind, $value] = array_pad(explode(':', (string) $serviceFilter, 2), 2, null);

                if ($kind === 'utility' && is_numeric($value)) {
                    $q->whereHas('serviceConfiguration', fn ($sq) => $sq->where('utility_service_id', (int) $value));
                    return;
                }

                if ($kind === 'type' && is_string($value) && $value !== '') {
                    $q->whereNull('service_configuration_id')->where('type', $value);
                }
            });
        }

        if ($buildingId) {
            $query->whereHas('meter.property', function ($q) use ($buildingId) {
                $q->where('building_id', $buildingId);
            });
        }

        $readings = $query->get();

        // Group by property
        $readingsByProperty = $readings->groupBy('meter.property.address');

        // Calculate totals by service (utility service preferred; legacy meters fall back to type)
        $consumptionByService = $readings->groupBy(function (MeterReading $reading): string {
            $utilityServiceId = $reading->meter?->serviceConfiguration?->utility_service_id;

            if (is_int($utilityServiceId)) {
                return "utility:{$utilityServiceId}";
            }

            return 'type:' . (string) $reading->meter?->type?->value;
        })->map(function ($serviceReadings) {
            $first = $serviceReadings->first();

            return [
                'label' => $first?->meter?->getServiceDisplayName() ?? __('app.common.na'),
                'unit' => $first?->meter?->getUnitOfMeasurement() ?? null,
                'count' => $serviceReadings->count(),
                'total' => $serviceReadings->sum('value'),
                'average' => $serviceReadings->avg('value'),
            ];
        })->sortByDesc('total');

        // Back-compat alias used by older views/tests
        $consumptionByType = $consumptionByService;

        // Monthly trend data (raw reading totals within the period)
        $monthlyTrend = $readings->groupBy(function (MeterReading $reading) {
            return $reading->reading_date->format('Y-m');
        })->map(function ($monthReadings) {
            return [
                'count' => $monthReadings->count(),
                'total' => $monthReadings->sum('value'),
            ];
        })->sortKeys();

        // Top consuming properties
        $topProperties = $readings->groupBy('meter.property_id')->map(function ($propertyReadings) {
            $property = $propertyReadings->first()->meter->property;
            return [
                'property' => $property,
                'total_consumption' => $propertyReadings->sum('value'),
                'reading_count' => $propertyReadings->count(),
            ];
        })->sortByDesc('total_consumption')->take(10);

        $properties = Property::all();
        $buildings = Building::all();
        $metersForOptions = Meter::query()
            ->with('serviceConfiguration.utilityService:id,name,unit_of_measurement')
            ->when($buildingId, fn ($q) => $q->whereHas('property', fn ($pq) => $pq->where('building_id', $buildingId)))
            ->when(isset($validated['property_id']), fn ($q) => $q->where('property_id', $validated['property_id']))
            ->get();

        $utilityServices = $metersForOptions
            ->map(fn (Meter $meter) => $meter->serviceConfiguration?->utilityService)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $legacyMeterTypes = $metersForOptions
            ->filter(fn (Meter $meter) => $meter->serviceConfiguration === null)
            ->map(fn (Meter $meter) => $meter->type?->value)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values();

        $serviceFilterOptions = [];
        foreach ($utilityServices as $service) {
            $unit = $service->unit_of_measurement ? " ({$service->unit_of_measurement})" : '';
            $serviceFilterOptions["utility:{$service->id}"] = "{$service->name}{$unit}";
        }
        foreach ($legacyMeterTypes as $type) {
            $label = \App\Enums\MeterType::tryFrom((string) $type)?->label() ?? ucfirst(str_replace('_', ' ', (string) $type));
            $serviceFilterOptions["type:{$type}"] = "Legacy: {$label}";
        }

        return view('manager.reports.consumption', compact(
            'readingsByProperty',
            'consumptionByType',
            'monthlyTrend',
            'topProperties',
            'startDate',
            'endDate',
            'properties',
            'buildings',
            'serviceFilterOptions',
            'serviceFilter',
            'buildingId'
        ));
    }

    /**
     * Export consumption report as CSV.
     */
    public function exportConsumption(ManagerConsumptionReportRequest $request): Response
    {
        $validated = $request->validated();

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $serviceFilter = $validated['service'] ?? null;

        if (!$serviceFilter && !empty($validated['meter_type'])) {
            $serviceFilter = 'type:' . (string) $validated['meter_type'];
        }

        $query = MeterReading::with([
            'meter.property',
            'meter.serviceConfiguration.utilityService',
        ])
            ->whereBetween('reading_date', [$startDate, $endDate]);

        if (isset($validated['property_id'])) {
            $query->whereHas('meter', function ($q) use ($validated) {
                $q->where('property_id', $validated['property_id']);
            });
        }

        if (!empty($validated['building_id'])) {
            $query->whereHas('meter.property', function ($q) use ($validated) {
                $q->where('building_id', $validated['building_id']);
            });
        }

        if ($serviceFilter) {
            $query->whereHas('meter', function ($q) use ($serviceFilter) {
                [$kind, $value] = array_pad(explode(':', (string) $serviceFilter, 2), 2, null);

                if ($kind === 'utility' && is_numeric($value)) {
                    $q->whereHas('serviceConfiguration', fn ($sq) => $sq->where('utility_service_id', (int) $value));
                    return;
                }

                if ($kind === 'type' && is_string($value) && $value !== '') {
                    $q->whereNull('service_configuration_id')->where('type', $value);
                }
            });
        }

        $readings = $query->get();

        $csv = "Date,Property,Meter Serial,Service,Unit,Value,Zone\n";
        foreach ($readings as $reading) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $reading->reading_date->format('Y-m-d'),
                $reading->meter->property->address ?? 'N/A',
                $reading->meter->serial_number,
                $reading->meter->getServiceDisplayName(),
                $reading->meter->getUnitOfMeasurement(),
                $reading->value,
                $reading->zone ?? '-'
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="consumption-report-' . $startDate . '-to-' . $endDate . '.csv"',
        ]);
    }

    /**
     * Generate revenue report by period.
     */
    public function revenue(ManagerRevenueReportRequest $request): View
    {
        $validated = $request->validated();

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $status = $validated['status'] ?? null;
        $buildingId = $validated['building_id'] ?? null;

        $query = Invoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->with('tenant.property.building');

        if ($status) {
            $query->where('status', $status);
        }

        if ($buildingId) {
            $query->whereHas('tenant.property', function ($q) use ($buildingId) {
                $q->where('building_id', $buildingId);
            });
        }

        $invoices = $query->get();

        $totalRevenue = $invoices->sum('total_amount');
        $paidRevenue = $invoices->where('status.value', 'paid')->sum('total_amount');
        $finalizedRevenue = $invoices->where('status.value', 'finalized')->sum('total_amount');
        $draftRevenue = $invoices->where('status.value', 'draft')->sum('total_amount');

        // Revenue by month
        $revenueByMonth = $invoices->groupBy(function ($invoice) {
            return $invoice->billing_period_start->format('Y-m');
        })->map(function ($monthInvoices) {
            return [
                'total' => $monthInvoices->sum('total_amount'),
                'paid' => $monthInvoices->where('status.value', 'paid')->sum('total_amount'),
                'count' => $monthInvoices->count(),
            ];
        })->sortKeys();

        // Revenue by building
        $revenueByBuilding = $invoices->groupBy(function ($invoice) {
            return $invoice->tenant?->property?->building?->name ?? 'Unassigned';
        })->map(function ($buildingInvoices) {
            return [
                'total' => $buildingInvoices->sum('total_amount'),
                'count' => $buildingInvoices->count(),
            ];
        })->sortByDesc('total');

        // Overdue invoices
        $overdueInvoices = $invoices->filter(function ($invoice) {
            return $invoice->due_date && 
                   $invoice->due_date->isPast() && 
                   !in_array($invoice->status->value, ['paid']);
        });

        $overdueAmount = $overdueInvoices->sum('total_amount');

        // Payment rate
        $paymentRate = $invoices->count() > 0 
            ? ($invoices->where('status.value', 'paid')->count() / $invoices->count()) * 100 
            : 0;

        $buildings = Building::all();

        return view('manager.reports.revenue', compact(
            'invoices',
            'totalRevenue',
            'paidRevenue',
            'finalizedRevenue',
            'draftRevenue',
            'revenueByMonth',
            'revenueByBuilding',
            'overdueInvoices',
            'overdueAmount',
            'paymentRate',
            'startDate',
            'endDate',
            'buildings',
            'status',
            'buildingId'
        ));
    }

    /**
     * Export revenue report as CSV.
     */
    public function exportRevenue(ManagerRevenueReportRequest $request): Response
    {
        $validated = $request->validated();

        $startDate = $validated['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $invoices = Invoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->with('tenant.property')
            ->get();

        $csv = "Invoice ID,Property,Period Start,Period End,Amount,Status,Due Date,Paid Date\n";
        foreach ($invoices as $invoice) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s\n",
                $invoice->id,
                $invoice->tenant?->property?->address ?? 'N/A',
                $invoice->billing_period_start->format('Y-m-d'),
                $invoice->billing_period_end->format('Y-m-d'),
                $invoice->total_amount,
                $invoice->status->value,
                $invoice->due_date?->format('Y-m-d') ?? '-',
                $invoice->paid_at?->format('Y-m-d') ?? '-'
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="revenue-report-' . $startDate . '-to-' . $endDate . '.csv"',
        ]);
    }

    /**
     * Generate meter reading compliance report.
     */
    public function meterReadingCompliance(ManagerMeterComplianceRequest $request): View
    {
        $validated = $request->validated();

        $month = $validated['month'] ?? Carbon::now()->format('Y-m');
        $buildingId = $validated['building_id'] ?? null;
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        // Get all properties with their meters
        $query = Property::with(['meters' => function ($query) use ($startDate, $endDate) {
            $query->with(['readings' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('reading_date', [$startDate, $endDate]);
            }]);
        }, 'building']);

        if ($buildingId) {
            $query->where('building_id', $buildingId);
        }

        $properties = $query->get();

        // Calculate compliance
        $propertiesWithReadings = $properties->filter(function ($property) {
            return $property->meters->count() > 0 && $property->meters->every(function ($meter) {
                return $meter->readings->isNotEmpty();
            });
        });

        $propertiesWithPartialReadings = $properties->filter(function ($property) {
            $metersWithReadings = $property->meters->filter(fn($meter) => $meter->readings->isNotEmpty())->count();
            return $metersWithReadings > 0 && $metersWithReadings < $property->meters->count();
        });

        $propertiesWithNoReadings = $properties->filter(function ($property) {
            return $property->meters->count() > 0 && $property->meters->every(function ($meter) {
                return $meter->readings->isEmpty();
            });
        });

        $complianceRate = $properties->count() > 0 
            ? ($propertiesWithReadings->count() / $properties->count()) * 100 
            : 0;

        // Compliance by building
        $complianceByBuilding = $properties->groupBy('building.name')->map(function ($buildingProperties) {
            $total = $buildingProperties->count();
            $complete = $buildingProperties->filter(function ($property) {
                return $property->meters->count() > 0 && $property->meters->every(function ($meter) {
                    return $meter->readings->isNotEmpty();
                });
            })->count();

            return [
                'total' => $total,
                'complete' => $complete,
                'rate' => $total > 0 ? ($complete / $total) * 100 : 0,
            ];
        });

        // Compliance by meter type
        $complianceByMeterType = [];
        foreach (MeterType::cases() as $type) {
            $meters = Meter::where('type', $type)->get();
            $metersWithReadings = $meters->filter(function ($meter) use ($startDate, $endDate) {
                return $meter->readings()->whereBetween('reading_date', [$startDate, $endDate])->exists();
            });

            $complianceByMeterType[$type->value] = [
                'total' => $meters->count(),
                'complete' => $metersWithReadings->count(),
                'rate' => $meters->count() > 0 ? ($metersWithReadings->count() / $meters->count()) * 100 : 0,
            ];
        }

        $buildings = Building::all();

        return view('manager.reports.meter-reading-compliance', compact(
            'properties',
            'propertiesWithReadings',
            'propertiesWithPartialReadings',
            'propertiesWithNoReadings',
            'complianceRate',
            'complianceByBuilding',
            'complianceByMeterType',
            'month',
            'buildings',
            'buildingId'
        ));
    }

    /**
     * Export compliance report as CSV.
     */
    public function exportCompliance(ManagerMeterComplianceRequest $request): Response
    {
        $validated = $request->validated();

        $month = $validated['month'] ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $properties = Property::with(['meters' => function ($query) use ($startDate, $endDate) {
            $query->with(['readings' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('reading_date', [$startDate, $endDate]);
            }]);
        }])->get();

        $csv = "Property,Building,Total Meters,Meters with Readings,Compliance Status\n";
        foreach ($properties as $property) {
            $totalMeters = $property->meters->count();
            $metersWithReadings = $property->meters->filter(fn($meter) => $meter->readings->isNotEmpty())->count();
            $status = $totalMeters > 0 && $totalMeters === $metersWithReadings ? 'Complete' : 'Incomplete';

            $csv .= sprintf(
                "%s,%s,%d,%d,%s\n",
                $property->address,
                $property->building?->name ?? 'N/A',
                $totalMeters,
                $metersWithReadings,
                $status
            );
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="compliance-report-' . $month . '.csv"',
        ]);
    }
}
