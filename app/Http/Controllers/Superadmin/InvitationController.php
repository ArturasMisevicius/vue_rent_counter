<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'plan_type' => ['required', Rule::in(['basic', 'professional', 'enterprise'])],
            'max_properties' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'expires_at' => ['required', 'date'],
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => null,
            'organization_name' => $validated['organization_name'],
            'email' => $validated['admin_email'],
            'role' => 'admin',
            'plan_type' => $validated['plan_type'],
            'max_properties' => $validated['max_properties'],
            'max_users' => $validated['max_users'],
            'status' => 'pending',
            'token' => Str::random(64),
            'expires_at' => $validated['expires_at'],
            'accepted_at' => null,
            'invited_by' => Auth::id(),
        ]);

        return redirect()
            ->route('superadmin.invitations.show', $invitation)
            ->with('success', 'Invitation created.');
    }

    public function show(OrganizationInvitation $invitation)
    {
        return view('pages.invitations.show', compact('invitation'));
    }

    public function resend(OrganizationInvitation $invitation)
    {
        $invitation->resend();

        return redirect()
            ->route('superadmin.invitations.show', $invitation)
            ->with('success', 'Invitation resent.');
    }

    public function cancel(OrganizationInvitation $invitation)
    {
        $invitation->delete();

        return redirect()
            ->route('superadmin.dashboard')
            ->with('success', 'Invitation cancelled.');
    }

    public function bulkResend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invitation_ids' => ['required', 'array', 'min:1'],
            'invitation_ids.*' => ['integer', 'exists:organization_invitations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $invitations = OrganizationInvitation::whereIn('id', $validated['invitation_ids'])->get();
        foreach ($invitations as $invitation) {
            $invitation->resend();
        }

        return response()->json(['success' => true]);
    }

    public function bulkCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invitation_ids' => ['required', 'array', 'min:1'],
            'invitation_ids.*' => ['integer', 'exists:organization_invitations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        OrganizationInvitation::whereIn('id', $validated['invitation_ids'])->delete();

        return response()->json(['success' => true]);
    }
}
