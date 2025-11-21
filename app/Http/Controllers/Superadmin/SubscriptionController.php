<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by plan type if provided
        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }
        
        // Filter expiring soon
        if ($request->filled('expiring_soon')) {
            $query->where('status', 'active')
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
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_type' => ['required', Rule::in(['basic', 'professional', 'enterprise'])],
            'status' => ['required', Rule::in(['active', 'expired', 'suspended', 'cancelled'])],
            'expires_at' => ['required', 'date'],
            'max_properties' => ['required', 'integer', 'min:1'],
            'max_tenants' => ['required', 'integer', 'min:1'],
        ]);
        
        $subscription->update($validated);
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    /**
     * Renew the specified subscription.
     * 
     * Requirements: 2.4
     */
    public function renew(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'expires_at' => ['required', 'date', 'after:today'],
        ]);
        
        $this->subscriptionService->renewSubscription(
            $subscription,
            \Carbon\Carbon::parse($validated['expires_at'])
        );
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', 'Subscription renewed successfully.');
    }

    /**
     * Suspend the specified subscription.
     * 
     * Requirements: 2.4
     */
    public function suspend(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        
        $this->subscriptionService->suspendSubscription($subscription, $validated['reason']);
        
        return redirect()
            ->route('superadmin.subscriptions.show', $subscription)
            ->with('success', 'Subscription suspended successfully.');
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
            ->with('success', 'Subscription cancelled successfully.');
    }
}
