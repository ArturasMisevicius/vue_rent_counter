<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\RenewSubscriptionRequest;
use App\Http\Requests\SuspendSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Display a listing of all subscriptions.
     * 
     * Requirements: 2.4, 2.5
     */
    public function index(Request $request)
    {
        $query = Subscription::with('user');
        
        // Filter by status if provided
        if ($request->filled('status') && in_array($request->status, SubscriptionStatus::values(), true)) {
            $query->where('status', $request->status);
        }
        
        // Filter by plan type if provided
        if ($request->filled('plan_type') && in_array($request->plan_type, SubscriptionPlanType::values(), true)) {
            $query->where('plan_type', $request->plan_type);
        }
        
        // Filter expiring soon
        if ($request->filled('expiring_soon')) {
            $query->where('status', SubscriptionStatus::ACTIVE->value)
                  ->where('expires_at', '<=', now()->addDays(14))
                  ->where('expires_at', '>=', now());
        }
        
        // Search by organization name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('organization_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'expires_at');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        $subscriptions = $query->paginate(20);
        
        return view('superadmin.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Display the specified subscription.
     * 
     * Requirements: 2.4, 2.5
     */
    public function show(Subscription $subscription)
    {
        $subscription->load('user');
        
        // Get subscription usage statistics
        $usage = [
            'properties_used' => $subscription->user->properties()->withoutGlobalScopes()->count(),
            'properties_limit' => $subscription->max_properties,
            'tenants_used' => $subscription->user->childUsers()->count(),
            'tenants_limit' => $subscription->max_tenants,
        ];
        
        return view('superadmin.subscriptions.show', compact('subscription', 'usage'));
    }

    /**
     * Show the form for editing the specified subscription.
     */
    public function edit(Subscription $subscription)
    {
        $subscription->load('user');
        
        return view('superadmin.subscriptions.edit', compact('subscription'));
    }

    /**
     * Update the specified subscription.
     * 
     * Requirements: 2.4, 2.5
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        
        $subscription->update($validated);
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', __('notifications.subscription.updated'));
    }

    /**
     * Renew the specified subscription.
     * 
     * Requirements: 2.4
     */
    public function renew(RenewSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        
        $this->subscriptionService->renewSubscription(
            $subscription,
            \Carbon\Carbon::parse($validated['expires_at'])
        );
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', __('notifications.subscription.renewed'));
    }

    /**
     * Suspend the specified subscription.
     * 
     * Requirements: 2.4
     */
    public function suspend(SuspendSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();
        
        $this->subscriptionService->suspendSubscription($subscription, $validated['reason']);
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', __('notifications.subscription.suspended'));
    }

    /**
     * Cancel the specified subscription.
     * 
     * Requirements: 2.4
     */
    public function cancel(Subscription $subscription)
    {
        $this->subscriptionService->cancelSubscription($subscription);
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', __('notifications.subscription.cancelled'));
    }
}
