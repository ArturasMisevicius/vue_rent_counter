<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function show(Property $property)
    {
        $this->assertTenantAccess($property);

        return response()->view('pages.properties.show', [
            'property' => $property,
        ]);
    }

    public function update(Request $request, Property $property)
    {
        $this->assertTenantAccess($property);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:65535'],
            'property_type' => ['required', 'string', 'in:'.implode(',', array_map(fn (PropertyType $type) => $type->value, PropertyType::cases()))],
        ]);

        $property->update([
            'address' => $validated['address'],
            'type' => PropertyType::from($validated['property_type']),
        ]);

        $actorId = session('impersonation.superadmin_id') ?: auth()->id();

        OrganizationActivityLog::create([
            'organization_id' => $property->tenant_id,
            'user_id' => $actorId,
            'action' => 'property_updated',
            'resource_type' => 'Property',
            'resource_id' => $property->id,
            'metadata' => [
                'property_id' => $property->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $property->id,
                'address' => $property->address,
                'type' => $property->type?->value,
                'name' => $property->name,
            ],
        ]);
    }

    private function assertTenantAccess(Property $property): void
    {
        $user = auth()->user();

        if (! $user || $user->tenant_id === null || $property->tenant_id !== $user->tenant_id) {
            abort(404);
        }
    }
}
