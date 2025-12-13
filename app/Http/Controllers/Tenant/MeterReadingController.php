<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MeterReadingSubmittedEmail;
use App\Services\MeterReadingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Tenant Meter Reading Controller
 * 
 * Handles meter reading operations for tenant users:
 * - Viewing meter reading history
 * - Submitting new meter readings
 * - Viewing individual meter reading details
 * 
 * Authorization: All actions require authenticated tenant user with assigned property
 * Multi-tenancy: Enforced via TenantScope and property ownership validation
 * 
 * @see MeterReadingService For business logic
 * @see StoreMeterReadingRequest For validation rules
 */
class MeterReadingController extends Controller
{
    private const READINGS_PER_PAGE = 20;

    public function __construct(
        private readonly MeterReadingService $meterReadingService
    ) {}

    /**
     * Display paginated list of meter readings for the authenticated tenant.
     * 
     * Performance: Eager loads meter relationship to prevent N+1 queries
     * Authorization: Only shows readings for tenant's assigned property
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tenant = $this->getTenantOrFail($user);
        $property = $user->property;
        
        // Eager load meter and property relationships to prevent N+1 queries
        $readings = $tenant->meterReadings()
            ->with(['meter.property', 'meter.serviceConfiguration.utilityService'])
            ->latest('reading_date')
            ->paginate(self::READINGS_PER_PAGE);

        // For submission form, load meters for the assigned property
        $properties = $this->getPropertiesForSubmission($property);

        return view('tenant.meter-readings.index', compact('readings', 'properties'));
    }

    /**
     * Display a specific meter reading.
     * 
     * Authorization: Verifies reading belongs to tenant's assigned property
     * 
     * @param Request $request
     * @param MeterReading $meterReading
     * @return View
     */
    public function show(Request $request, MeterReading $meterReading): View
    {
        $this->authorizeReadingAccess($request->user(), $meterReading);

        // Eager load relationships for display
        $meterReading->load(['meter.property', 'meter.serviceConfiguration.utilityService', 'tenant', 'enteredByUser']);

        return view('tenant.meter-readings.show', compact('meterReading'));
    }

    /**
     * Store a new meter reading submitted by tenant.
     * 
     * Validation: Handled by StoreMeterReadingRequest (monotonicity, zone support)
     * Notification: Sends email to parent user (admin) upon successful submission
     * 
     * @param StoreMeterReadingRequest $request
     * @return RedirectResponse
     */
    public function store(StoreMeterReadingRequest $request): RedirectResponse
    {
        $user = $request->user();
        $property = $this->getPropertyOrFail($user);
        $tenant = $this->getTenantOrFail($user);

        $validated = $request->validated();

        // Verify meter belongs to tenant's property
        $meter = $property->meters()
            ->where('id', $validated['meter_id'])
            ->firstOrFail();

        // Create meter reading via service layer
        $reading = $this->meterReadingService->createReading(
            meter: $meter,
            tenantId: $tenant->id,
            readingDate: $validated['reading_date'],
            value: $validated['value'],
            zone: $validated['zone'] ?? null,
            enteredByUserId: $user->id
        );

        // Notify parent user (admin/manager) about submission
        $this->notifyParentUser($user, $reading);

        return redirect()
            ->route('tenant.meter-readings.show', $reading)
            ->with('success', __('meter_readings.messages.submitted_successfully'));
    }

    /**
     * Get tenant or fail with 403.
     * 
     * @param User $user
     * @return Tenant
     */
    private function getTenantOrFail(User $user): Tenant
    {
        if (!$user->tenant) {
            abort(403, __('meter_readings.errors.no_tenant_assigned'));
        }

        return $user->tenant;
    }

    /**
     * Get property or fail with 403.
     * 
     * @param User $user
     * @return Property
     */
    private function getPropertyOrFail(User $user): Property
    {
        if (!$user->property) {
            abort(403, __('meter_readings.errors.no_property_assigned'));
        }

        return $user->property;
    }

    /**
     * Get properties collection for meter reading submission form.
     * 
     * @param Property|null $property
     * @return Collection
     */
    private function getPropertiesForSubmission(?Property $property): Collection
    {
        if (!$property) {
            return collect();
        }

        // Eager load meters to prevent N+1 in form rendering
        return collect([$property->load('meters.serviceConfiguration.utilityService')]);
    }

    /**
     * Authorize tenant access to meter reading.
     * 
     * @param User $user
     * @param MeterReading $meterReading
     * @return void
     */
    private function authorizeReadingAccess(User $user, MeterReading $meterReading): void
    {
        $property = $user->property;

        if (!$property || $meterReading->meter->property_id !== $property->id) {
            abort(403, __('meter_readings.errors.unauthorized_access'));
        }
    }

    /**
     * Notify parent user about meter reading submission.
     * 
     * @param User $user
     * @param MeterReading $reading
     * @return void
     */
    private function notifyParentUser(User $user, MeterReading $reading): void
    {
        if ($user->parentUser) {
            $user->parentUser->notify(new MeterReadingSubmittedEmail($reading, $user));
        }
    }
}
