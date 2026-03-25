<?php

use App\Filament\Support\Tenant\Portal\TenantPropertyPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('returns the assigned tenant name and email for the tenant information output', function () {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->create();

    $summary = app(TenantPropertyPresenter::class)->for($fixture->user->fresh());

    expect($summary['tenant_name'])->toBe($fixture->user->name)
        ->and($summary['tenant_email'])->toBe($fixture->user->email);
});
