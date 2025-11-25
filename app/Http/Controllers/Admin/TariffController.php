<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTariffRequest;
use App\Http\Requests\UpdateTariffRequest;
use App\Models\Provider;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * TariffController
 * 
 * Handles tariff management operations for administrators.
 * 
 * This controller provides CRUD operations for tariff configuration,
 * including support for tariff versioning and time-of-use pricing.
 * 
 * Authorization Architecture:
 * - Route Middleware: 'role:admin' restricts all routes to admin/superadmin only
 * - Controller Authorization: Policy checks provide fine-grained control
 * - Policy Layer: TariffPolicy defines role-based permissions
 * 
 * Note: While TariffPolicy allows managers to view tariffs, the route middleware
 * ensures only admins can access these admin-specific routes. Managers access
 * tariffs through Filament resources or API endpoints.
 * 
 * Requirements:
 * - 2.1: Store tariff configuration as JSON with flexible zone definitions
 * - 2.2: Validate time-of-use zones (no overlaps, 24-hour coverage)
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.2: Admin has full CRUD operations on tariffs
 * - 11.3: Manager has read-only access (via Filament, not these routes)
 * 
 * @package App\Http\Controllers\Admin
 */
final class TariffController extends Controller
{
    /**
     * Display a listing of tariffs.
     * 
     * Shows paginated list of all tariffs with their providers.
     * Supports sorting by name, active dates, and creation date.
     * 
     * Performance optimizations:
     * - Eager loads provider relationship to prevent N+1 queries
     * - Selects only required columns to reduce memory usage
     * - Uses query string pagination for better UX
     * 
     * Query Parameters:
     * - sort: Column to sort by (name, active_from, active_until, created_at)
     * - direction: Sort direction (asc, desc)
     * - page: Page number for pagination
     * 
     * Requirements: 11.1, 11.2
     * 
     * @param Request $request HTTP request with optional sort parameters
     * @return View Tariff index view with paginated tariffs
     */
    public function index(Request $request): View
    {
        // Authorize: Only admins and managers can view tariffs (Requirement 11.1)
        $this->authorize('viewAny', Tariff::class);
        
        // Performance: Select only required columns and eager load provider
        $query = Tariff::select([
            'id',
            'provider_id',
            'name',
            'configuration',
            'active_from',
            'active_until',
            'created_at',
        ])->with(['provider:id,name']);
        
        // Handle sorting with validated input
        $sortColumn = $request->input('sort', 'active_from');
        $sortDirection = $request->input('direction', 'desc');
        
        // Validate sort column to prevent SQL injection
        $allowedColumns = ['name', 'active_from', 'active_until', 'created_at'];
        if (!in_array($sortColumn, $allowedColumns, true)) {
            $sortColumn = 'active_from'; // Fallback to default
        }
        
        $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        
        // Performance: Use pagination with query string preservation
        $tariffs = $query->paginate(20)->withQueryString();
        
        return view('admin.tariffs.index', compact('tariffs'));
    }

    /**
     * Show the form for creating a new tariff.
     * 
     * Displays form with provider selection and configuration options
     * for both flat-rate and time-of-use tariff types.
     * 
     * Performance optimizations:
     * - Selects only id and name columns for provider dropdown
     * - Uses pluck for minimal memory footprint
     * 
     * Requirements: 11.1, 11.2
     * 
     * @return View Tariff creation form with providers list
     */
    public function create(): View
    {
        // Authorize: Only admins can create tariffs (Requirement 11.2)
        $this->authorize('create', Tariff::class);
        
        // Performance: Select only required columns for dropdown
        $providers = Provider::select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return view('admin.tariffs.create', compact('providers'));
    }

    /**
     * Store a newly created tariff in storage.
     * 
     * Creates a new tariff with validated configuration.
     * Configuration JSON is validated for structure and time-of-use zones.
     * 
     * Requirements:
     * - 2.1: Store tariff configuration as JSON
     * - 2.2: Validate time-of-use zones
     * - 11.1: Verify user's role
     * - 11.2: Admin can create tariffs
     * 
     * @param StoreTariffRequest $request Validated request with tariff data
     * @return RedirectResponse Redirect to tariff index with success message
     */
    public function store(StoreTariffRequest $request): RedirectResponse
    {
        // Authorize: Only admins can create tariffs (Requirement 11.2)
        $this->authorize('create', Tariff::class);
        
        $validated = $request->validated();

        // Create tariff with validated configuration (Requirements 2.1, 2.2)
        $tariff = Tariff::create($validated);
        
        // Log tariff creation for audit trail
        Log::info('Tariff created', [
            'user_id' => auth()->id(),
            'tariff_id' => $tariff->id,
            'provider_id' => $tariff->provider_id,
            'name' => $tariff->name,
            'type' => $tariff->configuration['type'] ?? 'unknown',
        ]);

        return redirect()->route('admin.tariffs.index')
            ->with('success', __('notifications.tariff.created'));
    }

    /**
     * Display the specified tariff.
     * 
     * Shows tariff details including configuration and version history.
     * Version history shows all tariffs with the same name and provider.
     * 
     * Performance optimizations:
     * - Eager loads provider relationship
     * - Selects only required columns for version history
     * - Limits version history to 10 most recent versions
     * 
     * Requirements: 11.1
     * 
     * @param Tariff $tariff The tariff to display
     * @return View Tariff detail view
     */
    public function show(Tariff $tariff): View
    {
        // Authorize: All authenticated users can view tariffs (Requirement 11.1)
        $this->authorize('view', $tariff);
        
        // Performance: Eager load provider if not already loaded
        $tariff->loadMissing('provider');
        
        // Performance: Get version history with selected columns and limit
        $versionHistory = Tariff::select([
            'id',
            'provider_id',
            'name',
            'configuration',
            'active_from',
            'active_until',
            'created_at',
        ])
            ->where('provider_id', $tariff->provider_id)
            ->where('name', $tariff->name)
            ->where('id', '!=', $tariff->id)
            ->orderBy('active_from', 'desc')
            ->limit(10)
            ->get();
        
        return view('admin.tariffs.show', compact('tariff', 'versionHistory'));
    }

    /**
     * Show the form for editing the specified tariff.
     * 
     * Performance optimizations:
     * - Selects only id and name columns for provider dropdown
     * - Eager loads provider relationship for current tariff
     * 
     * Requirements: 11.1, 11.2
     * 
     * @param Tariff $tariff The tariff to edit
     * @return View Tariff edit form
     */
    public function edit(Tariff $tariff): View
    {
        // Authorize: Only admins can update tariffs (Requirement 11.2)
        $this->authorize('update', $tariff);
        
        // Performance: Eager load provider if not already loaded
        $tariff->loadMissing('provider');
        
        // Performance: Select only required columns for dropdown
        $providers = Provider::select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return view('admin.tariffs.edit', compact('tariff', 'providers'));
    }

    /**
     * Update the specified tariff in storage.
     * 
     * Supports two modes:
     * 1. Direct update: Modifies the existing tariff
     * 2. Version creation: Closes current tariff and creates new version
     * 
     * Version creation is useful for maintaining historical tariff data
     * while introducing new rates.
     * 
     * Requirements:
     * - 2.1: Store tariff configuration as JSON
     * - 2.2: Validate time-of-use zones
     * - 11.1: Verify user's role
     * - 11.2: Admin can update tariffs
     * 
     * @param UpdateTariffRequest $request Validated request with tariff data
     * @param Tariff $tariff The tariff to update
     * @return RedirectResponse Redirect to tariff detail with success message
     */
    public function update(UpdateTariffRequest $request, Tariff $tariff): RedirectResponse
    {
        // Authorize: Only admins can update tariffs (Requirement 11.2)
        $this->authorize('update', $tariff);
        
        $validated = $request->validated();
        
        // If creating a new version, preserve the old tariff and create a new one
        if ($request->boolean('create_new_version')) {
            // Set end date for current tariff to day before new version starts
            $newActiveFrom = Carbon::parse($validated['active_from']);
            $tariff->update(['active_until' => $newActiveFrom->copy()->subDay()]);
            
            // Create new version (Requirements 2.1, 2.2)
            $newTariff = Tariff::create([
                'provider_id' => $validated['provider_id'],
                'name' => $validated['name'],
                'configuration' => $validated['configuration'],
                'active_from' => $validated['active_from'],
                'active_until' => $validated['active_until'] ?? null,
            ]);
            
            // Log version creation for audit trail
            Log::info('Tariff version created', [
                'user_id' => auth()->id(),
                'old_tariff_id' => $tariff->id,
                'new_tariff_id' => $newTariff->id,
                'provider_id' => $newTariff->provider_id,
                'name' => $newTariff->name,
            ]);
            
            return redirect()->route('admin.tariffs.show', $newTariff)
                ->with('success', __('notifications.tariff.version_created'));
        }

        // Otherwise, update the existing tariff (Requirements 2.1, 2.2)
        $tariff->update($validated);
        
        // Log tariff update for audit trail
        Log::info('Tariff updated', [
            'user_id' => auth()->id(),
            'tariff_id' => $tariff->id,
            'provider_id' => $tariff->provider_id,
            'name' => $tariff->name,
        ]);

        return redirect()->route('admin.tariffs.show', $tariff)
            ->with('success', __('notifications.tariff.updated'));
    }

    /**
     * Remove the specified tariff from storage.
     * 
     * Soft deletes the tariff. Can be restored later if needed.
     * 
     * Requirements: 11.1, 11.2
     * 
     * @param Tariff $tariff The tariff to delete
     * @return RedirectResponse Redirect to tariff index with success message
     */
    public function destroy(Tariff $tariff): RedirectResponse
    {
        // Authorize: Only admins can delete tariffs (Requirement 11.2)
        $this->authorize('delete', $tariff);
        
        // Log tariff deletion for audit trail
        Log::info('Tariff deleted', [
            'user_id' => auth()->id(),
            'tariff_id' => $tariff->id,
            'provider_id' => $tariff->provider_id,
            'name' => $tariff->name,
        ]);
        
        $tariff->delete();

        return redirect()->route('admin.tariffs.index')
            ->with('success', __('notifications.tariff.deleted'));
    }
}
