<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Enums\UserRole;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant Profile Page
 * 
 * Allows tenants to view and edit their profile information.
 * Restricted to basic profile fields - tenants cannot change
 * their role, property assignment, or other administrative data.
 * 
 * ## Features
 * - View profile information
 * - Edit name and email
 * - View property assignment (read-only)
 * - Change password
 * 
 * ## Security
 * - Role-based access control (TENANT only)
 * - Limited field editing
 * - Property assignment is read-only
 */
final class Profile extends Page
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT;
    }

    /**
     * Get data for the profile view.
     * 
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'user' => null,
                'property' => null,
            ];
        }

        // Load user with property relationship
        $property = null;
        if ($user->property_id) {
            $property = $user->property()->with([
                'building:id,name,address'
            ])->first();
        }

        return [
            'user' => $user,
            'property' => $property,
        ];
    }
}