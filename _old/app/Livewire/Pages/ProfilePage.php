<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use App\Support\EuropeanCurrencyOptions;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class ProfilePage extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return view('pages.profile.show', $this->buildViewData($user));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildViewData(User $user): array
    {
        return match ($user->role) {
            UserRole::SUPERADMIN => $this->buildSuperadminData($user),
            UserRole::ADMIN => $this->buildAdminData($user),
            UserRole::MANAGER => $this->buildManagerData($user),
            UserRole::TENANT => $this->buildTenantData($user),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSuperadminData(User $user): array
    {
        return [
            'user' => $user,
            'languages' => Language::query()->active()->orderBy('display_order')->get(),
            'currencyOptions' => EuropeanCurrencyOptions::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminData(User $user): array
    {
        $languages = Language::query()->active()->orderBy('display_order')->get();
        $currencyOptions = EuropeanCurrencyOptions::options();
        $subscription = $user->subscription;

        $subscriptionStatus = null;
        $daysUntilExpiry = null;
        $showExpiryWarning = false;
        $usageStats = null;

        if ($subscription !== null) {
            $daysUntilExpiry = $subscription->daysUntilExpiry();
            $showExpiryWarning = $daysUntilExpiry <= 14 && $daysUntilExpiry > 0;

            if ($subscription->isExpired()) {
                $subscriptionStatus = 'expired';
            } elseif ($showExpiryWarning) {
                $subscriptionStatus = 'expiring_soon';
            } else {
                $subscriptionStatus = 'active';
            }

            $propertiesCount = $user->properties()->count();
            $tenantsCount = $user->childUsers()->where('role', UserRole::TENANT)->count();

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

        return [
            'user' => $user,
            'subscription' => $subscription,
            'subscriptionStatus' => $subscriptionStatus,
            'daysUntilExpiry' => $daysUntilExpiry,
            'showExpiryWarning' => $showExpiryWarning,
            'usageStats' => $usageStats,
            'languages' => $languages,
            'currencyOptions' => $currencyOptions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildManagerData(User $user): array
    {
        $portfolioStats = [
            'properties' => Property::count(),
            'meters' => Meter::count(),
            'tenants' => Tenant::count(),
            'drafts' => Invoice::draft()->count(),
        ];

        return [
            'user' => $user,
            'portfolioStats' => $portfolioStats,
            'languages' => Language::query()->active()->orderBy('display_order')->get(),
            'currencyOptions' => EuropeanCurrencyOptions::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTenantData(User $user): array
    {
        $user->load(['property.building', 'parentUser']);

        return [
            'languages' => Language::query()->active()->orderBy('display_order')->get(),
            'user' => $user,
        ];
    }
}
