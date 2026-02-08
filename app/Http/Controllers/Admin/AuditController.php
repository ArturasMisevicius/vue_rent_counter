<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeterReadingAudit;

class AuditController extends Controller
{
    public function index()
    {
        // Only admins can access audit logs
        if (auth()->user()->role->value !== 'admin') {
            abort(403);
        }
        
        $query = MeterReadingAudit::with(['meterReading.meter', 'changedByUser']);
        
        // Filter by date range if provided
        if (request('from_date')) {
            $query->whereDate('created_at', '>=', request('from_date'));
        }
        
        if (request('to_date')) {
            $query->whereDate('created_at', '<=', request('to_date'));
        }
        
        // Filter by meter serial number if provided
        if (request('meter_serial')) {
            $query->whereHas('meterReading.meter', function ($q) {
                $q->where('serial_number', 'like', '%' . request('meter_serial') . '%');
            });
        }
        
        $audits = $query->latest()->paginate(50);
        
        return view('pages.audit.index-admin', compact('audits'));
    }
}
