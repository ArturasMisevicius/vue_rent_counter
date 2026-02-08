<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerUpdateProfileRequest;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use App\Support\EuropeanCurrencyOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $portfolioStats = [
            'properties' => Property::count(),
            'meters' => Meter::count(),
            'tenants' => Tenant::count(),
            'drafts' => Invoice::draft()->count(),
        ];

        return view('pages.profile.show-manager', [
            'user' => $request->user(),
            'portfolioStats' => $portfolioStats,
            'languages' => Language::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(),
            'currencyOptions' => EuropeanCurrencyOptions::options(),
        ]);
    }

    public function update(ManagerUpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->currency = $validated['currency'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', __('notifications.profile.updated'));
    }
}
