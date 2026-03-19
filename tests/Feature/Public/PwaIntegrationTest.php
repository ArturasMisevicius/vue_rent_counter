<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the public homepage without pwa metadata or service worker registration', function (): void {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('serviceWorker.register', false);
});

it('renders guest authentication pages without pwa metadata or service worker registration', function (): void {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('serviceWorker.register', false);
});

it('renders the tenant portal shell without pwa metadata or service worker registration', function (): void {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertDontSee('rel="manifest"', false)
        ->assertDontSee('serviceWorker.register', false);
});
