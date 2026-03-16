<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\LoginFormAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login demo users table with curated demo accounts', function (): void {
    User::factory()
        ->superadmin()
        ->create([
            'name' => 'System Superadmin',
            'email' => 'superadmin@example.com',
        ]);

    $admin = User::factory()
        ->admin(1)
        ->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ]);

    User::factory()
        ->manager(1)
        ->create([
            'name' => 'Demo Manager',
            'email' => 'manager@example.com',
            'parent_user_id' => $admin->id,
        ]);

    User::factory()
        ->tenant(1, null, $admin->id)
        ->create([
            'name' => 'Alina Petrauskienė',
            'email' => 'tenant.alina@example.com',
        ]);

    User::factory()
        ->tenant(1, null, $admin->id)
        ->create([
            'name' => 'Marius Jonaitis',
            'email' => 'tenant.marius@example.com',
        ]);

    User::factory()
        ->admin(2)
        ->create([
            'name' => 'Outside User',
            'email' => 'outside@example.com',
        ]);

    $viewData = app(LoginFormAccountService::class)->getViewData('admin');

    expect($viewData['accounts'])->toHaveCount(5)
        ->and(array_column($viewData['accounts'], 'email'))->toBe([
            'superadmin@example.com',
            'admin@example.com',
            'manager@example.com',
            'tenant.alina@example.com',
            'tenant.marius@example.com',
        ]);

    $html = view('filament.auth.demo-accounts', $viewData)->render();

    expect($html)
        ->toContain('<table')
        ->toContain('superadmin@example.com')
        ->toContain('manager@example.com')
        ->toContain('tenant.marius@example.com')
        ->toContain('password')
        ->not->toContain('outside@example.com');
});
