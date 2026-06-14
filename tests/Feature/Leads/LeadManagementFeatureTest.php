<?php

declare(strict_types=1);

use App\Enums\LeadOutreachChannel;
use App\Enums\LeadOutreachDirection;
use App\Enums\LeadOutreachStatus;
use App\Enums\ListingLeadStatus;
use App\Filament\Actions\Admin\Leads\AssignLead;
use App\Filament\Actions\Admin\Leads\ConvertLeadToProperty;
use App\Filament\Actions\Admin\Leads\ExportLeadsCsv;
use App\Filament\Actions\Admin\Leads\ImportLeadCsv;
use App\Filament\Actions\Admin\Leads\RecordOutreachActivity;
use App\Filament\Actions\Admin\Leads\ScheduleLeadFollowUp;
use App\Filament\Actions\Admin\Leads\ValidateLeadCsv;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\LeadContact;
use App\Models\LeadImportBatch;
use App\Models\LeadOutreachActivity;
use App\Models\LeadSource;
use App\Models\ListingLead;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('previews csv rows, reports invalid rows, detects duplicates, and writes audit', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $source = LeadSource::factory()->create([
        'organization_id' => $organization->id,
        'created_by_user_id' => $admin->id,
    ]);
    $contact = LeadContact::factory()->create([
        'organization_id' => $organization->id,
        'phone' => '+370 600 11111',
        'normalized_phone' => '+37060011111',
        'email' => 'owner@example.test',
        'normalized_email' => 'owner@example.test',
    ]);
    ListingLead::factory()->forOrganization($organization)->create([
        'lead_source_id' => $source->id,
        'lead_contact_id' => $contact->id,
        'source_url' => 'https://www.aruodas.lt/butai/vilniuje/100',
        'external_id' => '100',
    ]);

    $this->actingAs($admin);

    $preview = app(ValidateLeadCsv::class)->handle($admin, $organization, leadCsvPath([
        ['100', 'https://www.aruodas.lt/butai/vilniuje/100', '2-room flat', 'Vilnius st. 1', '120000', '+370 600 11111', 'owner@example.test', 'Nice flat'],
        ['101', 'https://www.aruodas.lt/butai/vilniuje/101', 'House', 'Kaunas st. 2', '180000', '+370 600 22222', 'bad-email', 'House'],
        ['102', 'https://www.aruodas.lt/butai/vilniuje/102', 'Studio', 'Klaipeda st. 3', 'not-money', '+370 600 33333', 'owner3@example.test', 'Studio'],
    ]));

    expect($preview['rows_total'])->toBe(3)
        ->and($preview['valid_rows'])->toBe(1)
        ->and($preview['invalid_rows'])->toBe(2)
        ->and($preview['possible_duplicates'])->toBe(1)
        ->and(collect($preview['errors'])->pluck('field')->all())->toContain('owner_email', 'price')
        ->and(collect($preview['rows'][0]['duplicates'])->pluck('type')->all())->toContain('source_url', 'external_id', 'normalized_phone', 'normalized_email');

    expect(AuditLog::query()->where('description', 'Lead import preview generated')->exists())->toBeTrue();
});

it('imports valid rows into a batch, creates contacts, stores mapping, and audits completion', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $source = LeadSource::factory()->create([
        'organization_id' => $organization->id,
        'created_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin);

    $preview = app(ValidateLeadCsv::class)->handle($admin, $organization, leadCsvPath([
        ['200', 'https://www.aruodas.lt/butai/vilniuje/200', 'Apartment', 'Vilnius st. 5', '99000', '+370 600 55555', 'lead@example.test', 'Fresh lead'],
    ]));

    $batch = app(ImportLeadCsv::class)->handle($admin, $organization, $source, $preview);

    expect($batch)->toBeInstanceOf(LeadImportBatch::class)
        ->and($batch->rows_total)->toBe(1)
        ->and($batch->rows_imported)->toBe(1)
        ->and($batch->mapping_config)->toHaveKey('source_url');

    expect(ListingLead::query()->where('source_url', 'https://www.aruodas.lt/butai/vilniuje/200')->exists())->toBeTrue()
        ->and(LeadContact::query()->where('normalized_email', 'lead@example.test')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('description', 'Lead CSV import completed')->exists())->toBeTrue();
});

it('updates an existing active listing for exact source duplicates instead of creating another active lead', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $source = LeadSource::factory()->create(['organization_id' => $organization->id]);
    ListingLead::factory()->forOrganization($organization)->create([
        'source_url' => 'https://www.aruodas.lt/butai/vilniuje/300',
        'external_id' => '300',
        'price' => '90000.00',
    ]);

    $this->actingAs($admin);

    $preview = app(ValidateLeadCsv::class)->handle($admin, $organization, leadCsvPath([
        ['300', 'https://www.aruodas.lt/butai/vilniuje/300', 'Updated apartment', 'Vilnius st. 7', '95000', '+370 600 77777', 'updated@example.test', 'Updated'],
    ]));

    app(ImportLeadCsv::class)->handle($admin, $organization, $source, $preview);

    expect(ListingLead::query()->where('source_url', 'https://www.aruodas.lt/butai/vilniuje/300')->count())->toBe(1);

    $lead = ListingLead::query()->firstWhere('source_url', 'https://www.aruodas.lt/butai/vilniuje/300');

    expect($lead?->listing_title)->toBe('Updated apartment')
        ->and((string) $lead?->price)->toBe('95000.00');
});

it('flags soft duplicates such as matching phone as duplicate leads for review', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $source = LeadSource::factory()->create(['organization_id' => $organization->id]);
    LeadContact::factory()->create([
        'organization_id' => $organization->id,
        'phone' => '+370 600 88888',
        'normalized_phone' => '+37060088888',
    ]);

    $this->actingAs($admin);

    $preview = app(ValidateLeadCsv::class)->handle($admin, $organization, leadCsvPath([
        ['401', 'https://www.aruodas.lt/butai/vilniuje/401', 'Another apartment', 'Vilnius st. 9', '88000', '+370 600 88888', 'new@example.test', 'Same phone'],
    ]));

    app(ImportLeadCsv::class)->handle($admin, $organization, $source, $preview);

    $lead = ListingLead::query()->where('source_url', 'https://www.aruodas.lt/butai/vilniuje/401')->firstOrFail();

    expect($lead->status)->toBe(ListingLeadStatus::DUPLICATE)
        ->and($lead->duplicate_reasons)->not->toBeEmpty();
});

it('allows assigned managers with lead permission to record outreach and schedule follow-ups', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $manager = User::factory()->manager()->create(['organization_id' => $organization->id]);
    ManagerPermission::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'resource' => 'leads',
        'can_create' => false,
        'can_edit' => true,
        'can_delete' => false,
    ]);
    $lead = ListingLead::factory()->forOrganization($organization)->create();

    $this->actingAs($admin);
    app(AssignLead::class)->handle($admin, $lead, $manager);

    $this->actingAs($manager);
    $activity = app(RecordOutreachActivity::class)->handle($manager, $lead->refresh(), [
        'channel' => LeadOutreachChannel::EMAIL->value,
        'direction' => LeadOutreachDirection::OUTBOUND->value,
        'message_summary' => 'Sent intro manually.',
        'next_follow_up_at' => now()->addDays(3)->toDateTimeString(),
    ]);

    $followUp = app(ScheduleLeadFollowUp::class)->handle($manager, $lead->refresh(), now()->addWeek()->toDateTimeString(), 'Call again');

    expect($activity->status)->toBe(LeadOutreachStatus::SENT)
        ->and($lead->refresh()->status)->toBe(ListingLeadStatus::FOLLOW_UP_NEEDED)
        ->and($lead->contact()->first()?->last_contacted_at)->not->toBeNull()
        ->and($followUp->status)->toBe(LeadOutreachStatus::SCHEDULED)
        ->and(LeadOutreachActivity::query()->count())->toBe(2)
        ->and(AuditLog::query()->where('description', 'Lead outreach recorded')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('description', 'Lead follow-up scheduled')->exists())->toBeTrue();
});

it('blocks outbound outreach when a contact is marked do-not-contact', function (): void {
    ['organization' => $organization] = createOrgWithAdmin();
    $manager = User::factory()->manager()->create(['organization_id' => $organization->id]);
    ManagerPermission::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'resource' => 'leads',
        'can_create' => false,
        'can_edit' => true,
        'can_delete' => false,
    ]);
    $contact = LeadContact::factory()->doNotContact()->create(['organization_id' => $organization->id]);
    $lead = ListingLead::factory()->forOrganization($organization)->create([
        'lead_contact_id' => $contact->id,
        'assigned_to_user_id' => $manager->id,
        'status' => ListingLeadStatus::DO_NOT_CONTACT,
    ]);

    $this->actingAs($manager);

    app(RecordOutreachActivity::class)->handle($manager, $lead, [
        'channel' => LeadOutreachChannel::EMAIL->value,
        'direction' => LeadOutreachDirection::OUTBOUND->value,
        'message_summary' => 'Trying to send',
    ]);
})->throws(ValidationException::class);

it('converts an interested lead to a property once and keeps the lead linked', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $building = Building::factory()->create(['organization_id' => $organization->id]);
    $lead = ListingLead::factory()->forOrganization($organization)->create([
        'status' => ListingLeadStatus::INTERESTED,
        'listing_title' => 'Lead apartment',
        'area' => '64.00',
    ]);

    $this->actingAs($admin);

    $property = app(ConvertLeadToProperty::class)->handle($admin, $lead, [
        'building_id' => $building->id,
        'unit_number' => 'L-1',
    ]);

    expect($property)->toBeInstanceOf(Property::class)
        ->and($property->name)->toBe('Lead apartment')
        ->and($lead->refresh()->status)->toBe(ListingLeadStatus::CONVERTED)
        ->and($lead->converted_property_id)->toBe($property->id)
        ->and(AuditLog::query()->where('description', 'Lead converted to property')->exists())->toBeTrue();

    app(ConvertLeadToProperty::class)->handle($admin, $lead->refresh(), [
        'building_id' => $building->id,
    ]);
})->throws(ValidationException::class);

it('enforces organization isolation, tenant denial, export permission, and export audit', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $otherOrganization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create(['organization_id' => $organization->id]);
    $lead = ListingLead::factory()->forOrganization($organization)->create();
    $otherLead = ListingLead::factory()->forOrganization($otherOrganization)->create();

    $this->actingAs($admin);

    expect(Gate::forUser($admin)->allows('view', $lead))->toBeTrue()
        ->and(Gate::forUser($admin)->denies('view', $otherLead))->toBeTrue()
        ->and(Gate::forUser($tenant)->denies('viewAny', ListingLead::class))->toBeTrue()
        ->and(Gate::forUser($tenant)->denies('export', ListingLead::class))->toBeTrue();

    $csv = app(ExportLeadsCsv::class)->handle($admin, $organization);

    expect($csv)->toContain('listing_title')
        ->and($csv)->toContain((string) $lead->listing_title)
        ->and($csv)->not->toContain((string) $otherLead->listing_title)
        ->and(AuditLog::query()->where('description', 'Lead CSV exported')->exists())->toBeTrue();
});

/**
 * @param  list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string}>  $rows
 */
function leadCsvPath(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'lead-csv-');
    expect($path)->toBeString();

    $handle = fopen($path, 'w');
    expect($handle)->not->toBeFalse();

    fputcsv($handle, ['ID', 'URL', 'Ad title', 'Address', 'Price', 'Phone', 'Email', 'Description']);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}
