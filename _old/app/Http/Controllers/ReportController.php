<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportExportRequest;
use App\Models\Invoice;
use App\Models\MeterReading;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function consumption(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $readings = MeterReading::with(['meter.property'])
            ->forPeriod($startDate, $endDate)
            ->get()
            ->groupBy('meter.type');

        return view('reports.consumption', compact('readings', 'startDate', 'endDate'));
    }

    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $invoices = Invoice::whereBetween('billing_period_start', [$startDate, $endDate])
            ->with('tenant')
            ->get();

        $totalRevenue = $invoices->sum('total_amount');
        $paidRevenue = $invoices->where('status', 'paid')->sum('total_amount');
        $unpaidRevenue = $invoices->where('status', 'finalized')->sum('total_amount');

        return view('reports.revenue', compact(
            'invoices',
            'totalRevenue',
            'paidRevenue',
            'unpaidRevenue',
            'startDate',
            'endDate'
        ));
    }

    public function outstanding()
    {
        $invoices = Invoice::finalized()
            ->with('tenant')
            ->latest()
            ->get();

        $totalOutstanding = $invoices->sum('total_amount');

        return view('reports.outstanding', compact('invoices', 'totalOutstanding'));
    }

    public function meterReadings(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $readings = MeterReading::with(['meter.property', 'enteredBy'])
            ->forPeriod($startDate, $endDate)
            ->latest('reading_date')
            ->get();

        return view('reports.meter-readings', compact('readings', 'startDate', 'endDate'));
    }

    public function tariffComparison()
    {
        // Future: Compare tariffs across providers
        return view('reports.tariff-comparison');
    }

    public function export(ReportExportRequest $request)
    {
        $validated = $request->validated();

        // Future: Export implementation
        return response()->json(['message' => __('reports.errors.export_pending')]);
    }
}
