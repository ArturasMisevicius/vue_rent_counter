<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvitationAcceptanceController extends Controller
{
    public function accept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::query()
            ->where('token', $token)
            ->firstOrFail();

        abort_if($invitation->accepted_at !== null, 404);
        abort_if($invitation->expires_at->isPast(), 404);

        $validated = $request->validate([
            'admin_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($invitation, $validated) {
            $organization = Organization::create([
                'name' => $invitation->organization_name ?? 'New Organization',
                'email' => $invitation->email,
                'plan' => SubscriptionPlan::tryFrom((string) $invitation->plan_type) ?? SubscriptionPlan::BASIC,
                'max_properties' => $invitation->max_properties ?? 100,
                'max_users' => $invitation->max_users ?? 10,
                'is_active' => true,
                'created_by_admin_id' => $invitation->invited_by,
            ]);

            User::create([
                'tenant_id' => $organization->id,
                'name' => $validated['admin_name'],
                'email' => $invitation->email,
                'password' => $validated['password'],
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'organization_name' => $organization->name,
            ]);

            $invitation->update([
                'organization_id' => $organization->id,
                'accepted_at' => now(),
                'status' => 'accepted',
            ]);
        });

        return redirect()->route('login');
    }
}

