<?php

use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Models\Meter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Configuration;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new Configuration)
        ->inChrome()
        ->timeout(15_000);
});

it('lets tenants open reading request notifications from the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $tenantWorkspace = createTenantInOrg($workspace['admin']);
    $tenantWorkspace['tenant']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    Meter::factory()
        ->for($workspace['organization'])
        ->for($tenantWorkspace['property'])
        ->create([
            'name' => 'Main electricity meter',
            'identifier' => 'MTR-BROWSER-READING',
        ]);

    app(OpenReadingInvoiceCycleAction::class)->handle($workspace['organization'], [
        'billing_period_start' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
        'due_date' => now()->subMonthNoOverflow()->endOfMonth()->addDays(14)->toDateString(),
    ], $workspace['admin']);

    $readingPath = route('filament.admin.pages.tenant-submit-meter-reading', [], false);

    visit(route('tenant.home', [], false))
        ->assertPathIs('/login')
        ->type('#email', $tenantWorkspace['tenant']->email)
        ->type('#password', 'password')
        ->press(__('auth.login_button'))
        ->wait()
        ->assertSee($tenantWorkspace['tenant']->name)
        ->click('[data-shell-notifications-slot="desktop"] [data-shell-notifications="center"] > button')
        ->assertSee(__('admin.invoices.reading_request.database_title'))
        ->press(__('admin.invoices.reading_request.database_title'))
        ->wait()
        ->assertPathIs($readingPath)
        ->assertSee(__('tenant.pages.readings.title'))
        ->assertSee(__('tenant.pages.readings.invoice_request_heading', [
            'number' => 'INV-',
        ]))
        ->assertSee('MTR-BROWSER-READING')
        ->assertNoJavaScriptErrors();
});
