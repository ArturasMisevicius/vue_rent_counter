<?php

use App\Enums\KycVerificationStatus;
use App\Filament\Pages\Profile;
use App\Models\UserKycProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant kyc sections on the shared profile page', function () {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSee('data-tenant-layout="standard"', false)
        ->assertSee('class="flex flex-col gap-5"', false)
        ->assertSee('md:flex-row md:flex-wrap', false)
        ->assertDontSee('grid gap-4 md:grid-cols-2', false)
        ->assertSeeText(__('shell.profile.kyc.sections.identity_verification.heading'))
        ->assertSeeText(__('shell.profile.kyc.fields.full_legal_name'))
        ->assertSeeText(__('shell.profile.kyc.sections.emergency_contacts.heading'))
        ->assertSeeText(__('shell.profile.kyc.sections.professional_information.heading'))
        ->assertSeeText(__('shell.profile.kyc.sections.banking_details.heading'))
        ->assertDontSeeText(__('shell.profile.kyc.fields.payment_history_score'))
        ->assertDontSeeText(__('shell.profile.kyc.fields.external_credit_bureau_reference'))
        ->assertDontSeeText(__('shell.profile.kyc.fields.internal_credit_score'))
        ->assertDontSeeText(__('shell.profile.kyc.fields.blacklist_status'));
});

it('saves tenant kyc data and document uploads from the shared profile page', function () {
    Storage::fake('local');

    $tenant = TenantPortalFactory::new()->create();

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('kycForm.full_legal_name', 'Taylor Tenant')
        ->set('kycForm.birth_date', '1990-05-14')
        ->set('kycForm.nationality', 'Lithuanian')
        ->set('kycForm.gender', 'female')
        ->set('kycForm.marital_status', 'single')
        ->set('kycForm.tax_id_number', 'LT-123456789')
        ->set('kycForm.social_security_number', '39005140000')
        ->set('kycForm.facial_recognition_consent', true)
        ->set('kycForm.secondary_contact_name', 'Jordan Tenant')
        ->set('kycForm.secondary_contact_relationship', 'Sibling')
        ->set('kycForm.secondary_contact_phone', '+37061111111')
        ->set('kycForm.secondary_contact_email', 'jordan@example.test')
        ->set('kycForm.tertiary_contact_name', 'Morgan Tenant')
        ->set('kycForm.tertiary_contact_relationship', 'Parent')
        ->set('kycForm.tertiary_contact_phone', '+37062222222')
        ->set('kycForm.tertiary_contact_email', 'morgan@example.test')
        ->set('kycForm.employer_name', 'Northwind Logistics')
        ->set('kycForm.employment_position', 'Analyst')
        ->set('kycForm.employment_contract_type', 'full_time')
        ->set('kycForm.monthly_income_range', '2000_2999')
        ->set('kycForm.iban', 'LT121000011101001000')
        ->set('kycForm.swift_bic', 'HABALT22')
        ->set('kycForm.bank_name', 'Hansa Bank')
        ->set('kycForm.bank_account_holder_name', 'Taylor Tenant')
        ->set('kycForm.profile_photo', UploadedFile::fake()->image('profile-photo.jpg'))
        ->set('kycForm.passport_scan', UploadedFile::fake()->create('passport.pdf', 32, 'application/pdf'))
        ->set('kycForm.national_id_front', UploadedFile::fake()->image('national-id-front.jpg'))
        ->set('kycForm.national_id_back', UploadedFile::fake()->image('national-id-back.jpg'))
        ->set('kycForm.drivers_license', UploadedFile::fake()->image('drivers-license.jpg'))
        ->set('kycForm.employment_verification_letter', UploadedFile::fake()->create('employment-letter.pdf', 24, 'application/pdf'))
        ->set('kycForm.direct_debit_mandate', UploadedFile::fake()->create('direct-debit-mandate.pdf', 24, 'application/pdf'))
        ->call('saveChanges')
        ->assertHasNoErrors();

    $profile = $tenant->user->fresh()->kycProfile;

    expect($profile)->not->toBeNull()
        ->and($profile?->full_legal_name)->toBe('Taylor Tenant')
        ->and($profile?->bank_name)->toBe('Hansa Bank')
        ->and($profile?->verification_status)->toBe(KycVerificationStatus::PENDING)
        ->and($profile?->attachments()->where('document_type', 'passport')->exists())->toBeTrue()
        ->and($profile?->attachments()->where('document_type', 'direct_debit_mandate')->exists())->toBeTrue();
});

it('does not resubmit an existing verified kyc profile when only account details change', function () {
    $tenant = TenantPortalFactory::new()->create();

    UserKycProfile::query()->create([
        'user_id' => $tenant->user->id,
        'organization_id' => $tenant->organization->id,
        'full_legal_name' => 'Taylor Tenant Legal',
        'birth_date' => '1990-05-14',
        'nationality' => 'Lithuanian',
        'gender' => 'female',
        'marital_status' => 'single',
        'verification_status' => KycVerificationStatus::VERIFIED,
        'reviewed_at' => now(),
    ]);

    Livewire::actingAs($tenant->user)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->call('saveChanges')
        ->assertHasNoErrors();

    expect($tenant->user->fresh()->kycProfile?->verification_status)->toBe(KycVerificationStatus::VERIFIED);
});
