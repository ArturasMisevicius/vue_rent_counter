<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerUpdateProfileRequest;
use App\Models\Language;
use App\Support\EuropeanCurrencyOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('pages.profile.show-superadmin', [
            'user' => $request->user(),
            'languages' => Language::query()->active()->orderBy('display_order')->get(),
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
