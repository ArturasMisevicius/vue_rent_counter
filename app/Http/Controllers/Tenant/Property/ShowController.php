<?php

namespace App\Http\Controllers\Tenant\Property;

use App\Http\Controllers\Controller;
use App\Support\Tenant\Portal\TenantPropertyPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowController extends Controller
{
    public function __invoke(Request $request, TenantPropertyPresenter $presenter): View
    {
        return view('tenant.property.show', [
            'summary' => $presenter->for($request->user()),
        ]);
    }
}
