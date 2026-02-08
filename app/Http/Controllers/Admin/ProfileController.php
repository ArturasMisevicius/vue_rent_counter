<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdatePasswordRequest;
use App\Http\Requests\AdminUpdateProfileRequest;
use App\Models\Language;
use App\Support\EuropeanCurrencyOptions;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display the admin's profile and subscription information.
     */
    public function show()
    {
        $user = auth()->user();
        $languages = Language::query()->active()->orderBy('display_order')->get();
        $currencyOptions = EuropeanCurrencyOptions::options();

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
                'usageStats',
                'languages',
                'currencyOptions'
            ));
        }

        return view('admin.profile.show', compact('user', 'languages', 'currencyOptions'));
    }

    /**
     * Update the admin's profile information.
     */
    public function update(AdminUpdateProfileRequest $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        $user->update($validated);

        return back()->with('success', __('notifications.profile.updated'));
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(AdminUpdatePasswordRequest $request)
    {
        $validated = $request->validated();

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', __('notifications.profile.password_updated'));
    }
}
