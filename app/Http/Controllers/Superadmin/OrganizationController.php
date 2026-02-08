<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pages.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('pages.organizations.create');
    }

    public function store(StoreOrganizationRequest $request)
    {
        $validated = $request->validated();

        $organization = Organization::create([
            ...$validated,
            'created_by_admin_id' => Auth::id(),
        ]);

        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', 'Organization created.');
    }

    public function show(Organization $organization)
    {
        return view('pages.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization)
    {
        return view('pages.organizations.edit', compact('organization'));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization)
    {
        $validated = $request->validated();

        $organization->update($validated);

        OrganizationActivityLog::create([
            'organization_id' => $organization->id,
            'user_id' => Auth::id(),
            'action' => 'organization_updated',
            'resource_type' => 'Organization',
            'resource_id' => $organization->id,
            'metadata' => [
                'updated_fields' => array_keys($validated),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', 'Organization updated.');
    }

    public function deactivate(Organization $organization)
    {
        $organization->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Deactivated by superadmin',
        ]);

        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', 'Organization deactivated.');
    }

    public function reactivate(Organization $organization)
    {
        $organization->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return redirect()
            ->route('superadmin.organizations.show', $organization)
            ->with('success', 'Organization reactivated.');
    }

    public function destroy(Organization $organization)
    {
        $hasDependencies = User::where('tenant_id', $organization->id)->exists()
            || Property::where('tenant_id', $organization->id)->exists()
            || Building::where('tenant_id', $organization->id)->exists()
            || Tenant::where('tenant_id', $organization->id)->exists()
            || Meter::where('tenant_id', $organization->id)->exists()
            || Invoice::where('tenant_id', $organization->id)->exists();

        if ($hasDependencies) {
            return response()->json([
                'message' => 'Organization has dependencies and cannot be deleted.',
                'errors' => [
                    'dependencies' => ['Organization has dependent records.'],
                ],
            ], 422);
        }

        $organization->forceDelete();

        return redirect()
            ->route('superadmin.organizations.index')
            ->with('success', 'Organization deleted.');
    }

    public function bulkSuspend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $organizationIds = $request->input('organization_ids', []);

            if (! is_array($organizationIds) || $organizationIds === []) {
                return;
            }

            $uniqueIds = array_values(array_unique($organizationIds));
            $existingCount = Organization::whereIn('id', $uniqueIds)->count();

            if ($existingCount !== count($uniqueIds)) {
                $validator->errors()->add('organization_ids', 'One or more organization IDs are invalid.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $organizationIds = $validated['organization_ids'];
        $reason = $validated['reason'];

        Organization::whereIn('id', $organizationIds)->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        foreach ($organizationIds as $organizationId) {
            OrganizationActivityLog::create([
                'organization_id' => $organizationId,
                'user_id' => Auth::id(),
                'action' => 'bulk_suspend',
                'resource_type' => 'Organization',
                'resource_id' => $organizationId,
                'metadata' => ['reason' => $reason],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function bulkReactivate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        Organization::whereIn('id', $validated['organization_ids'])->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function bulkChangePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
            'new_plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $newPlan = SubscriptionPlan::from((string) $validated['new_plan']);

        Organization::whereIn('id', $validated['organization_ids'])->update([
            'plan' => $newPlan->value,
            'max_properties' => $newPlan->getMaxProperties(),
            'max_users' => $newPlan->getMaxUsers(),
        ]);

        return response()->json(['success' => true]);
    }

    public function bulkExport(Request $request)
    {
        $format = $request->input('format', 'csv');
        abort_unless($format === 'csv', 422);

        $includeInactive = (bool) $request->boolean('include_inactive', false);

        $query = Organization::query();
        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        $organizations = $query->orderBy('name')->get(['name', 'email', 'plan', 'is_active']);

        $lines = [];
        $lines[] = 'Name,Email,Plan,Status';

        foreach ($organizations as $organization) {
            $lines[] = implode(',', [
                $this->escapeCsv($organization->name),
                $this->escapeCsv($organization->email),
                $this->escapeCsv($organization->plan->value),
                $organization->is_active ? 'active' : 'inactive',
            ]);
        }

        $csv = implode("\n", $lines)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="organizations.csv"',
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', 'exists:organizations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $deleted = [];
        $failed = [];

        foreach ($validated['organization_ids'] as $organizationId) {
            $organization = Organization::find($organizationId);

            if (! $organization) {
                $failed[] = $organizationId;

                continue;
            }

            $hasDependencies = User::where('tenant_id', $organization->id)->exists()
                || Property::where('tenant_id', $organization->id)->exists()
                || Building::where('tenant_id', $organization->id)->exists()
                || Tenant::where('tenant_id', $organization->id)->exists()
                || Meter::where('tenant_id', $organization->id)->exists()
                || Invoice::where('tenant_id', $organization->id)->exists();

            if ($hasDependencies) {
                $failed[] = $organizationId;

                continue;
            }

            $organization->forceDelete();
            $deleted[] = $organizationId;
        }

        $allSucceeded = count($failed) === 0;
        $partialSuccess = ! $allSucceeded && count($deleted) > 0;

        return response()->json([
            'success' => $allSucceeded,
            'partial_success' => $partialSuccess,
            'deleted_ids' => $deleted,
            'failed_ids' => $failed,
        ]);
    }

    private function escapeCsv(string $value): string
    {
        $escaped = str_replace('"', '""', $value);

        if (str_contains($escaped, ',') || str_contains($escaped, '"') || str_contains($escaped, "\n")) {
            return "\"{$escaped}\"";
        }

        return $escaped;
    }
}
