<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\MeterReading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single-action controller for meter reading corrections.
 * 
 * This controller handles meter reading updates with full audit trail support.
 * Separated from the main MeterReadingController to emphasize the importance
 * of corrections and maintain single responsibility.
 * 
 * Security Features:
 * - Authorization via MeterReadingPolicy (Requirements 11.1, 11.3, 7.3)
 * - Rate limiting via RateLimitMeterReadingOperations middleware
 * - Comprehensive security logging with PII redaction
 * - Error handling with generic user messages
 * - Database transactions for atomicity
 * 
 * Requirements:
 * - 1.1: Store reading with entered_by user ID and timestamp
 * - 1.2: Validate monotonicity (reading cannot be lower than previous)
 * - 1.3: Validate temporal validity (reading date not in future)
 * - 1.4: Maintain audit trail of changes
 * - 8.1: Create audit record in meter_reading_audit table
 * - 8.2: Store old value, new value, reason, and user who made change
 * - 8.3: Recalculate affected draft invoices
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can update meter readings within tenant
 * - 7.3: Cross-tenant access prevention
 * 
 * @package App\Http\Controllers
 */
final class MeterReadingUpdateController extends Controller
{
    /**
     * Update a meter reading with audit trail.
     * 
     * This method:
     * 1. Authorizes the update via MeterReadingPolicy (CRITICAL SECURITY)
     * 2. Logs security event BEFORE update
     * 3. Validates the new reading value against monotonicity rules (in FormRequest)
     * 4. Wraps update in database transaction for atomicity
     * 5. Sets the change_reason for the observer to capture
     * 6. Updates the reading (observer automatically creates audit record)
     * 7. Recalculates affected draft invoices (handled by observer)
     * 8. Logs security event AFTER successful update
     * 9. Handles errors gracefully with user-friendly messages
     * 
     * Security Measures:
     * - Authorization check prevents unauthorized access
     * - Rate limiting prevents DoS attacks (20 updates/hour)
     * - Security logging captures all attempts
     * - Error handling prevents information disclosure
     * - Transaction ensures audit trail integrity
     * 
     * Performance Optimizations:
     * - Explicit authorization check before processing
     * - Database transaction ensures atomicity (audit + recalculation)
     * - Eager loading via route model binding reduces N+1 queries
     * - Query count: ~3-5 total (1 auth + 1 update + 1-3 observer queries)
     * 
     * @param UpdateMeterReadingRequest $request Validated request with new value and change reason
     * @param MeterReading $meterReading The reading to update (eager loaded via route binding)
     * @return RedirectResponse Redirect back with success message
     * 
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot update reading
     */
    public function __invoke(
        UpdateMeterReadingRequest $request,
        MeterReading $meterReading
    ): RedirectResponse {
        // CRITICAL: Authorize before any operations (Requirements 11.1, 11.3, 7.3)
        // Ensures only authorized users (admins, managers within tenant) can update
        // Throws AuthorizationException (403) if unauthorized
        $this->authorize('update', $meterReading);
        
        $validated = $request->validated();
        $oldValue = $meterReading->value;
        $newValue = $validated['value'];
        
        // Security logging BEFORE update (Requirement 8.1)
        // Captures all update attempts for forensic analysis
        // RedactSensitiveData processor will sanitize PII
        Log::info('Meter reading update initiated', [
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role->value,
            'meter_reading_id' => $meterReading->id,
            'meter_id' => $meterReading->meter_id,
            'tenant_id' => $meterReading->tenant_id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'change_reason' => $validated['change_reason'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        try {
            // Wrap in transaction for atomicity
            // Ensures audit record and invoice recalculation happen together or not at all
            DB::transaction(function () use ($meterReading, $validated, $newValue) {
                // Set change_reason for the observer to use in audit trail
                // This temporary attribute is captured by MeterReadingObserver::updating()
                $meterReading->change_reason = $validated['change_reason'];
                
                // Update the reading - observer will automatically:
                // 1. Create MeterReadingAudit record with old/new values (Requirement 8.1, 8.2)
                // 2. Recalculate affected draft invoices (Requirement 8.3)
                // 3. Prevent recalculation of finalized invoices
                $meterReading->update([
                    'value' => $newValue,
                    'reading_date' => $validated['reading_date'] ?? $meterReading->reading_date,
                    'zone' => $validated['zone'] ?? $meterReading->zone,
                ]);
            });
            
            // Security logging AFTER successful update
            // Confirms successful completion for audit trail
            Log::info('Meter reading updated successfully', [
                'user_id' => auth()->id(),
                'meter_reading_id' => $meterReading->id,
                'affected_invoices' => 'calculated_by_observer',
            ]);
            
            return redirect()
                ->back()
                ->with('success', __('meter_readings.updated_successfully'));
                
        } catch (\Exception $e) {
            // Security logging for failures
            // Captures errors for debugging without exposing to users
            Log::error('Meter reading update failed', [
                'user_id' => auth()->id(),
                'meter_reading_id' => $meterReading->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Return user-friendly error message (no sensitive information)
            // Prevents information disclosure through error messages
            return redirect()
                ->back()
                ->withErrors(['error' => __('meter_readings.update_failed')])
                ->withInput();
        }
    }
}
