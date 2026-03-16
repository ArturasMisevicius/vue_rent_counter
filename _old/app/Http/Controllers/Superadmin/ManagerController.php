<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ManagerController extends Controller
{
    public function index(Request $request)
    {
        $managers = User::withoutGlobalScopes()
            ->where('role', 'manager')
            ->withCount([
                'properties',
                'buildings',
                'invoices',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('pages.managers.index', compact('managers'));
    }

    public function show(User $manager)
    {
        if ($manager->role->value !== 'manager') {
            abort(404);
        }

        $manager->load([
            'properties' => fn ($q) => $q->with('building')->withCount('tenants'),
            'buildings',
            'invoices' => fn ($q) => $q->latest('billing_period_start')->limit(10),
        ]);

        return view('pages.managers.show', compact('manager'));
    }
}
