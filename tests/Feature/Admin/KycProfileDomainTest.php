<?php

use App\Enums\KycVerificationStatus;
use App\Models\Attachment;
use App\Models\UserKycProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('stores a kyc profile with encrypted sensitive fields and typed attachments', function () {
    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    $profile = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'nationality' => 'Lithuanian',
        'gender' => 'female',
        'marital_status' => 'single',
        'tax_id_number' => 'LT-123456789',
        'social_security_number' => '39005140000',
        'facial_recognition_consent' => true,
        'secondary_contact_name' => 'Jordan Tenant',
        'secondary_contact_relationship' => 'Sibling',
        'secondary_contact_phone' => '+37061111111',
        'secondary_contact_email' => 'jordan@example.test',
        'tertiary_contact_name' => 'Morgan Tenant',
        'tertiary_contact_relationship' => 'Parent',
        'tertiary_contact_phone' => '+37062222222',
        'tertiary_contact_email' => 'morgan@example.test',
        'employer_name' => 'Northwind Logistics',
        'employment_position' => 'Analyst',
        'employment_contract_type' => 'full_time',
        'monthly_income_range' => '2000_2999',
        'iban' => 'LT121000011101001000',
        'swift_bic' => 'HABALT22',
        'bank_name' => 'Hansa Bank',
        'bank_account_holder_name' => 'Taylor Tenant',
        'payment_history_score' => 88,
        'external_credit_bureau_reference' => 'CBR-REF-123',
        'internal_credit_score' => 712,
        'blacklist_status' => false,
        'verification_status' => KycVerificationStatus::UNVERIFIED,
    ]);

    Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($profile, 'attachable')
        ->create([
            'document_type' => 'passport',
            'path' => 'kyc/passport.pdf',
        ]);

    Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($profile, 'attachable')
        ->create([
            'document_type' => 'profile_photo',
            'mime_type' => 'image/jpeg',
            'filename' => 'profile-photo.jpg',
            'original_filename' => 'profile-photo.jpg',
            'path' => 'kyc/profile-photo.jpg',
        ]);

    expect($tenant->fresh()->kycProfile)->not->toBeNull()
        ->and($tenant->fresh()->kycProfile->full_legal_name)->toBe('Taylor Tenant')
        ->and($tenant->fresh()->kycProfile->verification_status)->toBe(KycVerificationStatus::UNVERIFIED)
        ->and($profile->attachments()->where('document_type', 'passport')->exists())->toBeTrue()
        ->and($profile->attachments()->where('document_type', 'profile_photo')->exists())->toBeTrue();

    $row = DB::table('user_kyc_profiles')->where('id', $profile->id)->first();

    expect($row)->not->toBeNull()
        ->and($row?->tax_id_number)->not->toBe('LT-123456789')
        ->and($row?->social_security_number)->not->toBe('39005140000')
        ->and($row?->iban)->not->toBe('LT121000011101001000')
        ->and($row?->swift_bic)->not->toBe('HABALT22');
});
