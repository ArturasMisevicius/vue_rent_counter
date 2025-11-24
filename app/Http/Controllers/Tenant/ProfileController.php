<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantUpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        // Eager load relationships for profile display
        $user->load(['property.building', 'parentUser']);
        
        return view('tenant.profile.show', ['user' => $user]);
    }

    public function update(TenantUpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();
        
        // Verify current password if changing password
        if (!empty($validated['password'])) {
            if (empty($validated['current_password'])) {
                return back()->withErrors(['current_password' => __('app.auth.current_password_required')]);
            }
            
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => __('app.auth.current_password_incorrect')]);
            }
            
            $user->password = Hash::make($validated['password']);
        }
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return back()->with('success', __('notifications.profile.updated'));
    }
}
