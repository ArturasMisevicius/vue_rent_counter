<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('tenant');

        // Handle search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Handle role filter
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // Handle sorting
        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort column to prevent SQL injection
        $allowedColumns = ['name', 'email', 'role', 'created_at'];
        if (in_array($sortColumn, $allowedColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest();
        }

        $users = $query->paginate(20)->withQueryString();

        return view('pages.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('create', User::class);

        $tenants = Tenant::with('property')->orderBy('name')->get();

        return view('pages.users.create', compact('tenants'));
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', __('notifications.user.created'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load(['tenant']);

        return view('pages.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $tenants = Tenant::with('property')->orderBy('name')->get();

        return view('pages.users.edit', compact('user', 'tenants'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.index')
            ->with('success', __('notifications.user.updated'));
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Check if user has associated data (meter readings)
        if ($user->meterReadings()->exists()) {
            return redirect()->route('admin.users.index')
                ->with('error', __('users.errors.has_readings'));
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', __('notifications.user.deleted'));
    }
}
