<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('optimization indexes are present for new tables', function () {
    $orgIndexes = collect(DB::select("PRAGMA index_list('organizations')"))->pluck('name');
    expect($orgIndexes)->toContain('organizations_status_subscription_index')
        ->and($orgIndexes)->toContain('organizations_plan_index')
        ->and($orgIndexes)->toContain('organizations_created_by_index');

    $activityIndexes = collect(DB::select("PRAGMA index_list('organization_activity_logs')"))->pluck('name');
    expect($activityIndexes)->toContain('org_activity_org_created_index')
        ->and($activityIndexes)->toContain('org_activity_user_created_index')
        ->and($activityIndexes)->toContain('org_activity_action_index');

    $invitationIndexes = collect(DB::select("PRAGMA index_list('organization_invitations')"))->pluck('name');
    expect($invitationIndexes)->toContain('org_invites_org_email_index')
        ->and($invitationIndexes)->toContain('org_invites_expires_at_index');

    $meterAuditIndexes = collect(DB::select("PRAGMA index_list('meter_reading_audits')"))->pluck('name');
    expect($meterAuditIndexes)->toContain('meter_reading_audits_meter_index')
        ->and($meterAuditIndexes)->toContain('meter_reading_audits_changed_by_index');
});
