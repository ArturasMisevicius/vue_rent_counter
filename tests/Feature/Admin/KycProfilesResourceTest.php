<?php

use App\Enums\KycVerificationStatus;
use App\Filament\Resources\UserKycProfiles\Pages\EditUserKycProfile;
use App\Filament\Resources\UserKycProfiles\Pages\ListUserKycProfiles;
use App\Filament\Resources\UserKycProfiles\Pages\ViewUserKycProfile;
use App\Models\Attachment;
use App\Models\UserKycProfile;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows organization-scoped kyc resource pages with verification badges', function () {
    $workspaceA = createOrgWithAdmin();
    $tenantA = createTenantInOrg($workspaceA['admin'])['tenant'];

    $workspaceB = createOrgWithAdmin();
    $tenantB = createTenantInOrg($workspaceB['admin'])['tenant'];

    $profileA = UserKycProfile::query()->create([
        'user_id' => $tenantA->id,
        'organization_id' => $workspaceA['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'nationality' => 'Lithuanian',
        'gender' => 'female',
        'marital_status' => 'single',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    $profileB = UserKycProfile::query()->create([
        'user_id' => $tenantB->id,
        'organization_id' => $workspaceB['organization']->id,
        'full_legal_name' => 'Morgan Tenant',
        'birth_date' => '1988-03-10',
        'nationality' => 'Latvian',
        'gender' => 'male',
        'marital_status' => 'married',
        'verification_status' => KycVerificationStatus::VERIFIED,
    ]);

    actingAs($workspaceA['admin']);

    get(route('filament.admin.resources.user-kyc-profiles.index'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.user_kyc_profiles.plural'))
        ->assertSeeText('Taylor Tenant')
        ->assertSeeText('Pending')
        ->assertDontSeeText('Morgan Tenant');

    get(route('filament.admin.resources.user-kyc-profiles.view', $profileA))
        ->assertSuccessful()
        ->assertSeeText('Taylor Tenant')
        ->assertSeeText('Pending');

    actingAs($workspaceA['admin']);

    Livewire::test(ListUserKycProfiles::class)
        ->assertTableColumnExists('full_legal_name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.user_kyc_profiles.columns.full_legal_name'))
        ->assertTableColumnExists('verification_status', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.user_kyc_profiles.columns.verification_status'))
        ->assertTableFilterExists('verification_status', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.user_kyc_profiles.filters.verification_status'))
        ->assertCanSeeTableRecords([$profileA])
        ->assertCanNotSeeTableRecords([$profileB]);
});

it('lets admins verify and reject kyc profiles from the review page', function () {
    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    $pendingForVerification = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'nationality' => 'Lithuanian',
        'gender' => 'female',
        'marital_status' => 'single',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    $pendingForRejection = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant Secondary',
        'birth_date' => '1990-05-14',
        'nationality' => 'Lithuanian',
        'gender' => 'female',
        'marital_status' => 'single',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    actingAs($workspace['admin']);

    Livewire::test(ViewUserKycProfile::class, ['record' => $pendingForVerification->getRouteKey()])
        ->callAction('verify')
        ->assertHasNoActionErrors();

    Livewire::test(ViewUserKycProfile::class, ['record' => $pendingForRejection->getRouteKey()])
        ->callAction('reject', data: [
            'rejection_reason' => 'Passport scan is unreadable.',
        ])
        ->assertHasNoActionErrors();

    expect($pendingForVerification->fresh()->verification_status)->toBe(KycVerificationStatus::VERIFIED)
        ->and($pendingForRejection->fresh()->verification_status)->toBe(KycVerificationStatus::REJECTED)
        ->and($pendingForRejection->fresh()->rejection_reason)->toBe('Passport scan is unreadable.');
});

it('lets admins open stored kyc attachments through the protected attachment route', function () {
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
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    $directory = storage_path('app/private/kyc-test');

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $path = $directory.'/passport.pdf';
    file_put_contents($path, 'kyc-passport-pdf');

    $attachment = Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($profile, 'attachable')
        ->create([
            'document_type' => 'passport',
            'path' => 'kyc-test/passport.pdf',
        ]);

    actingAs($workspace['admin']);

    get(route('kyc.attachments.show', $attachment))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('exposes attachment upload fields on the admin edit page with image-focused profile photo behavior', function () {
    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    $profile = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    actingAs($workspace['admin']);

    Livewire::test(EditUserKycProfile::class, ['record' => $profile->getRouteKey()])
        ->assertFormFieldExists('profile_photo', fn (FormFileUpload $field): bool => $field->getAcceptedFileTypes() === ['image/*']
            && $field->hasImageEditor()
            && $field->getAutomaticallyResizeImagesWidth() !== null
            && $field->getAutomaticallyResizeImagesHeight() !== null)
        ->assertFormFieldExists('passport_scan', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable())
        ->assertFormFieldExists('national_id_front', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable())
        ->assertFormFieldExists('national_id_back', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable())
        ->assertFormFieldExists('drivers_license', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable())
        ->assertFormFieldExists('employment_verification_letter', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable())
        ->assertFormFieldExists('direct_debit_mandate', fn (FormFileUpload $field): bool => $field->isOpenable() && $field->isDownloadable());
});

it('lets admins replace kyc attachments from the edit page', function () {
    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    $profile = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    actingAs($workspace['admin']);

    Livewire::test(EditUserKycProfile::class, ['record' => $profile->getRouteKey()])
        ->fillForm([
            'profile_photo' => UploadedFile::fake()->image('profile-photo.jpg'),
            'passport_scan' => UploadedFile::fake()->create('passport.pdf', 24, 'application/pdf'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($profile->fresh()->attachments()->where('document_type', 'profile_photo')->exists())->toBeTrue()
        ->and($profile->fresh()->attachments()->where('document_type', 'passport')->exists())->toBeTrue();
});

it('shows a photo preview entry and extension-based document icon on the admin review page', function () {
    $workspace = createOrgWithAdmin();
    $tenant = createTenantInOrg($workspace['admin'])['tenant'];

    $profile = UserKycProfile::query()->create([
        'user_id' => $tenant->id,
        'organization_id' => $workspace['organization']->id,
        'full_legal_name' => 'Taylor Tenant',
        'birth_date' => '1990-05-14',
        'verification_status' => KycVerificationStatus::PENDING,
    ]);

    Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($profile, 'attachable')
        ->create([
            'document_type' => 'profile_photo',
            'filename' => 'profile-photo.jpg',
            'original_filename' => 'profile-photo.jpg',
            'mime_type' => 'image/jpeg',
            'path' => 'kyc-test/profile-photo.jpg',
        ]);

    Attachment::factory()
        ->for($workspace['organization'])
        ->for($workspace['admin'], 'uploader')
        ->for($profile, 'attachable')
        ->create([
            'document_type' => 'passport',
            'filename' => 'passport.pdf',
            'original_filename' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'path' => 'kyc-test/passport.pdf',
        ]);

    actingAs($workspace['admin']);

    Livewire::test(ViewUserKycProfile::class, ['record' => $profile->getRouteKey()])
        ->assertSchemaComponentExists('document_profile_photo', 'infolist', fn (ImageEntry $entry): bool => $entry->getDiskName() === 'local')
        ->assertSchemaComponentExists('document_passport', 'infolist', fn (TextEntry $entry): bool => $entry->getIcon('passport.pdf') === Heroicon::OutlinedDocumentText);
});
