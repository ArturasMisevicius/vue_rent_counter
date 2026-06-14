<?php

use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pest\Browser\Configuration;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new Configuration)
        ->inChrome()
        ->timeout(15_000);
});

it('exposes the tenant lease agreement upload field to admins in the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $tenant = createTenantInOrg($workspace['admin'])['tenant'];
    $editPath = route('filament.admin.resources.tenants.edit', ['record' => $tenant], false);

    visit($editPath)
        ->assertPathIs('/login')
        ->type('#email', $workspace['admin']->email)
        ->type('#password', 'password')
        ->press(__('auth.login_button'))
        ->wait()
        ->assertPathIs($editPath)
        ->assertSee(__('admin.tenants.sections.lease_agreement'))
        ->assertSee(__('admin.tenants.messages.lease_agreement_hint'))
        ->assertSee(__('admin.tenants.fields.lease_agreement'))
        ->assertCount('input[type="file"]', 1)
        ->assertNoJavaScriptErrors();
});

it('shows attached tenant lease agreements to admins in the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $tenant = createTenantInOrg($workspace['admin'])['tenant'];
    $fileName = 'tenant-lease-browser-'.Str::uuid().'.pdf';
    $storedPath = TenantLeaseAgreement::DIRECTORY.'/'.$fileName;

    Storage::disk(TenantLeaseAgreement::DISK)->put($storedPath, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

    $attachment = $tenant->attachments()->save(
        Attachment::factory()
            ->for($workspace['organization'])
            ->for($workspace['admin'], 'uploader')
            ->make([
                'document_type' => TenantLeaseAgreement::DOCUMENT_TYPE,
                'filename' => $fileName,
                'original_filename' => $fileName,
                'mime_type' => 'application/pdf',
                'disk' => TenantLeaseAgreement::DISK,
                'path' => $storedPath,
                'size' => Storage::disk(TenantLeaseAgreement::DISK)->size($storedPath),
            ]),
    );

    $viewPath = route('filament.admin.resources.tenants.view', ['record' => $tenant], false);
    $attachmentPath = route('tenant.attachments.show', ['attachment' => $attachment], false);

    try {
        visit($viewPath)
            ->assertPathIs('/login')
            ->type('#email', $workspace['admin']->email)
            ->type('#password', 'password')
            ->press(__('auth.login_button'))
            ->wait()
            ->assertPathIs($viewPath)
            ->assertSee(__('admin.tenants.sections.lease_agreement'))
            ->assertSee($fileName)
            ->assertAttributeContains("a[href*=\"{$attachmentPath}\"]", 'href', $attachmentPath)
            ->assertNoJavaScriptErrors();
    } finally {
        Storage::disk(TenantLeaseAgreement::DISK)->delete($storedPath);
    }

    $leaseAgreement = $tenant->fresh()->leaseAgreement;

    expect($leaseAgreement)->not->toBeNull()
        ->and($leaseAgreement?->document_type)->toBe(TenantLeaseAgreement::DOCUMENT_TYPE)
        ->and($leaseAgreement?->original_filename)->toBe($fileName)
        ->and($leaseAgreement?->organization_id)->toBe($workspace['organization']->id)
        ->and($leaseAgreement?->uploaded_by_user_id)->toBe($workspace['admin']->id);
});
