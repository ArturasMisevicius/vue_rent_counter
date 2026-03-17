<?php

namespace App\Http\Controllers\Tenant\Readings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function __invoke(Request $request): View
    {
        $tenant = User::query()
            ->select(['id', 'organization_id'])
            ->with([
                'currentPropertyAssignment:id,property_id,tenant_user_id,unassigned_at',
                'currentPropertyAssignment.property:id,organization_id,name',
                'currentPropertyAssignment.property.meters' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'unit'])
                    ->orderBy('name'),
            ])
            ->findOrFail($request->user()->id);

        return view('tenant.readings.create', [
            'breadcrumbs' => [
                new BreadcrumbItemData(__('tenant.navigation.home'), route('tenant.home')),
                new BreadcrumbItemData(__('tenant.pages.readings.heading')),
            ],
            'currentProperty' => $tenant->currentProperty,
            'meters' => $tenant->currentProperty?->meters ?? collect(),
        ]);
    }
}
