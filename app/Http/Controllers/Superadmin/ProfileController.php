<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerUpdateProfileRequest;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('superadmin.profile.show', [
            'user' => $request->user(),
            'languages' => Language::query()->active()->orderBy('display_order')->get(),
        ]);
    }

    public function update(ManagerUpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', __('notifications.profile.updated'));
    }
}
