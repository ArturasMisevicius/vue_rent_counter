<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the public homepage with pwa metadata and service worker registration', function (): void {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee('serviceWorker.register', false);
});

it('renders guest authentication pages with pwa metadata and service worker registration', function (): void {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee('serviceWorker.register', false);
});

it('renders the tenant portal shell with pwa metadata and service worker registration', function (): void {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee('serviceWorker.register', false);
});
