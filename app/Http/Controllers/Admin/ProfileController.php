<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the admin's profile and subscription information.
     */
    public function show()
    {
        $user = auth()->user();
        
        // Load subscription if admin role
        if ($user->role->value === 'admin') {
            $subscription = $user->subscription;
            
            // Calculate subscription status
            $subscriptionStatus = null;
            $daysUntilExpiry = null;
            $showExpiryWarning = false;
            
            if ($subscription) {
                $daysUntilExpiry = $subscription->daysUntilExpiry();
                $showExpiryWarning = $daysUntilExpiry <= 14 && $daysUntilExpiry > 0;
                
                if ($subscription->isExpired()) {
                    $subscriptionStatus = 'expired';
                } elseif ($showExpiryWarning) {
                    $subscriptionStatus = 'expiring_soon';
                } else {
                    $subscriptionStatus = 'active';
                }
            }
            
            // Get usage statistics
            $usageStats = null;
            if ($subscription) {
                $propertiesCount = $user->properties()->count();
                $tenantsCount = $user->childUsers()->where('role', 'tenant')->count();
                
                $usageStats = [
                    'properties_used' => $propertiesCount,
                    'properties_max' => $subscription->max_properties,
                    'properties_percentage' => $subscription->max_properties > 0 
                        ? round(($propertiesCount / $subscription->max_properties) * 100) 
                        : 0,
                    'tenants_used' => $tenantsCount,
                    'tenants_max' => $subscription->max_tenants,
                    'tenants_percentage' => $subscription->max_tenants > 0 
                        ? round(($tenantsCount / $subscription->max_tenants) * 100) 
                        : 0,
                ];
            }
            
            return view('admin.profile.show', compact(
                'user',
                'subscription',
                'subscriptionStatus',
                'daysUntilExpiry',
                'showExpiryWarning',
                'usageStats'
            ));
        }
        
        return view('admin.profile.show', compact('user'));
    }

    /**
     * Update the admin's profile information.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'organization_name' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
