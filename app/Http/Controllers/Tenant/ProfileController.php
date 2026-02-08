<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantUpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Tenant Profile Controller
 * 
 * Handles tenant profile viewing and updating functionality.
 * Tenants can update their name, email, and password through this controller.
 * 
 * @package App\Http\Controllers\Tenant
 * 
 * Requirements Addressed:
 * - 16.1: Tenant can view their profile information
 * - 16.2: Tenant can update their name and email
 * - 16.3: Tenant can change their password securely
 * - 16.4: Profile displays assigned property and manager contact
 * 
 * Security Features:
 * - CSRF protection via middleware
 * - Current password verification for password changes
 * - Password hashing with bcrypt
 * - Email uniqueness validation
 * - Role-based access control (tenant only)
 * 
 * @see TenantUpdateProfileRequest For validation rules
 * @see routes/web.php For route definitions
 */
class ProfileController extends Controller
{
    /**
     * Display the tenant's profile page.
     * 
     * Shows tenant information including:
     * - Basic profile data (name, email, role)
     * - Assigned property details
     * - Building information (if applicable)
     * - Manager/admin contact information
     * 
     * @param Request $request The HTTP request instance
     * @return View The profile view with user data
     * 
     * @throws \Illuminate\Auth\AuthenticationException If user is not authenticated
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        
        // Eager load relationships to avoid N+1 queries
        // - property: Tenant's assigned property
        // - property.building: Building the property belongs to
        // - parentUser: Admin/manager who created this tenant account
        $user->load(['property.building', 'parentUser']);
        
        return view('tenant.profile.show', ['user' => $user]);
    }

    /**
     * Update the tenant's profile information.
     * 
     * Allows tenants to update:
     * - Name (required)
     * - Email (required, must be unique)
     * - Password (optional, requires current password verification)
     * 
     * Password Update Flow:
     * 1. If password field is provided, current_password is required
     * 2. Current password is verified via Laravel's current_password rule
     * 3. New password must be at least 8 characters and confirmed
     * 4. Password is hashed with bcrypt before storage
     * 
     * @param TenantUpdateProfileRequest $request The validated form request
     * @return RedirectResponse Redirects back with success message
     * 
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * @throws \Illuminate\Database\QueryException If database update fails
     * 
     * @see TenantUpdateProfileRequest For validation rules and authorization
     */
    public function update(TenantUpdateProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        
        // Update basic profile information
        // Using fill() for mass assignment protection
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        
        // Update password if provided
        // Note: current_password validation already passed in FormRequest
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();

        return back()->with('success', __('tenant.profile.updated_successfully'));
    }
}
