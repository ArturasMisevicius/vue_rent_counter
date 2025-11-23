<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        $users = \App\Models\User::with(['property', 'subscription'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('auth.login', compact('users'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            
            // Check if account is deactivated (Requirements: 7.1, 8.4)
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();
            
            // Redirect based on role (Requirements: 1.1, 8.1)
            // Note: Using direct redirect() instead of intended() to ensure correct dashboard
            return match($user->role->value) {
                'superadmin' => redirect('/superadmin/dashboard'),
                'admin' => redirect('/admin/dashboard'),
                'manager' => redirect('/manager/dashboard'),
                'tenant' => redirect('/tenant/dashboard'),
                default => redirect('/'),
            };
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
