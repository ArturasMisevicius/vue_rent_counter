<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\SecurityViolation;
use App\Models\User;
use App\Notifications\Projects\ProjectOverdueAlertNotification;
use App\Notifications\Projects\ProjectStalledAlertNotification;
use App\Notifications\Projects\ProjectUnapprovedEscalationNotification;
use App\Notifications\Projects\ProjectUnapprovedReminderNotification;
use App\Services\Operations\BackupRestoreReadinessService;
use App\Services\Operations\PhaseOneGuardrailsBranchProtectionService;
use App\Services\Operations\ReleaseReadinessEvidenceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ops:backup-restore-readiness', function (BackupRestoreReadinessService $backupRestoreReadinessService): int {
    $report = $backupRestoreReadinessService->assess();

    $this->components->info('Backup and Restore Readiness');

    foreach ($report['checks'] as $check) {
        $this->components->twoColumnDetail(
            $check['label'],
            strtoupper(str_replace('_', ' ', $check['status'])).' — '.$check['summary'],
        );
    }

    $this->newLine();
    $this->line('Result: '.($report['ready'] ? 'READY' : 'NOT READY'));

    return $report['ready'] ? self::SUCCESS : self::FAILURE;
})->purpose('Verify backup and restore prerequisites for the current environment');

Artisan::command('ops:release-readiness', function (ReleaseReadinessEvidenceService $releaseReadinessEvidenceService): int {
    $report = $releaseReadinessEvidenceService->gather();

    $this->components->info('Release Readiness Evidence');
    $this->components->twoColumnDetail('Integration health', $report['integration_health']);
    $this->components->twoColumnDetail('Backup and restore', $report['backup_and_restore']);
    $this->components->twoColumnDetail('Queue worker', $report['queue_worker']);
    $this->components->twoColumnDetail('Manual checks', $report['manual_checks']);

    $this->newLine();
    $this->line('Result: '.($report['ready'] ? 'EVIDENCE CAPTURED' : 'EVIDENCE CAPTURED WITH FAILURES'));

    return self::SUCCESS;
})->purpose('Collect release-readiness evidence for the current milestone');

Artisan::command('ops:phase1-guardrails-branch-protection', function (PhaseOneGuardrailsBranchProtectionService $phaseOneGuardrailsBranchProtectionService): int {
    $report = $phaseOneGuardrailsBranchProtectionService->report();

    $this->components->info('Phase 1 Guardrails Branch Protection');
    $this->components->twoColumnDetail('Repository', $report['repository']);
    $this->components->twoColumnDetail('Branch', $report['branch']);
    $this->components->twoColumnDetail('Required check', $report['required_check']);
    $this->components->twoColumnDetail('API endpoint', $report['endpoint']);
    $this->components->twoColumnDetail(
        'Token status',
        $report['token_configured'] ? 'Configured in current environment' : 'Missing in current environment',
    );

    $this->newLine();
    $this->line('Payload');
    $this->line($report['payload_json']);

    $this->newLine();
    $this->line('Apply command');
    $this->line($report['apply_command']);

    $this->newLine();
    $this->line('Verify command');
    $this->line($report['verify_command']);

    $this->newLine();
    $this->line('Result: '.($report['token_configured'] ? 'READY TO APPLY' : 'WAITING FOR GITHUB_TOKEN'));

    return self::SUCCESS;
})->purpose('Print the exact GitHub API payload and commands for Phase 1 guardrails branch protection');

Artisan::command('projects:alert-stalled', function (): int {
    Project::query()
        ->select([
            'id',
            'organization_id',
            'manager_id',
            'name',
            'reference_number',
            'status',
            'metadata',
            'updated_at',
        ])
        ->with([
            'organization:id,name,owner_user_id',
            'manager:id,organization_id,name,email,role',
        ])
        ->where('status', ProjectStatus::ON_HOLD)
        ->chunkById(100, function ($projects): void {
            foreach ($projects as $project) {
                $reasonUpdatedAt = data_get($project->metadata, 'on_hold_reason_updated_at');

                if (blank($reasonUpdatedAt)) {
                    continue;
                }

                if (Carbon::parse((string) $reasonUpdatedAt)->gte(now()->subDays(30)->startOfSecond())) {
                    continue;
                }

                $recipients = collect([$project->manager])
                    ->filter()
                    ->merge(
                        User::query()
                            ->select(['id', 'organization_id', 'name', 'email', 'role'])
                            ->where('organization_id', $project->organization_id)
                            ->whereIn('role', ['admin'])
                            ->get(),
                    )
                    ->unique('id')
                    ->values();

                Notification::send($recipients, new ProjectStalledAlertNotification($project));
            }
        });

    return self::SUCCESS;
})->purpose('Send alerts for projects stalled on hold for more than 30 days');

Artisan::command('projects:alert-overdue', function (): int {
    Project::query()
        ->select([
            'id',
            'organization_id',
            'manager_id',
            'name',
            'reference_number',
            'status',
            'estimated_end_date',
            'metadata',
        ])
        ->with([
            'organization:id,name,owner_user_id',
            'manager:id,organization_id,name,email,role',
        ])
        ->whereIn('status', [ProjectStatus::PLANNED, ProjectStatus::IN_PROGRESS])
        ->whereDate('estimated_end_date', '<', today())
        ->chunkById(100, function ($projects): void {
            foreach ($projects as $project) {
                $metadata = $project->metadata ?? [];

                if (($metadata['overdue'] ?? false) !== true) {
                    $metadata['overdue'] = true;
                    $project->forceFill(['metadata' => $metadata])->saveQuietly();
                }

                $recipients = collect([$project->manager])
                    ->filter()
                    ->merge(
                        User::query()
                            ->select(['id', 'organization_id', 'name', 'email', 'role'])
                            ->where('organization_id', $project->organization_id)
                            ->whereIn('role', ['admin'])
                            ->get(),
                    )
                    ->unique('id')
                    ->values();

                Notification::send($recipients, new ProjectOverdueAlertNotification($project));
            }
        });

    return self::SUCCESS;
})->purpose('Send overdue alerts for projects that are past their estimated end date');

Artisan::command('projects:alert-unapproved', function (): int {
    Project::query()
        ->select([
            'id',
            'organization_id',
            'manager_id',
            'name',
            'reference_number',
            'status',
            'requires_approval',
            'approved_at',
            'created_at',
        ])
        ->with([
            'organization:id,name,owner_user_id',
            'manager:id,organization_id,name,email,role',
        ])
        ->where('status', ProjectStatus::PLANNED)
        ->where('requires_approval', true)
        ->whereNull('approved_at')
        ->where('created_at', '<=', now()->subDays(14))
        ->chunkById(100, function ($projects): void {
            $superadmins = User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role'])
                ->where('role', 'superadmin')
                ->get();

            foreach ($projects as $project) {
                $approvers = User::query()
                    ->select(['id', 'organization_id', 'name', 'email', 'role'])
                    ->where('organization_id', $project->organization_id)
                    ->whereIn('role', ['admin'])
                    ->get();

                Notification::send($approvers, new ProjectUnapprovedReminderNotification($project));

                if ($project->created_at <= now()->subDays(30)) {
                    Notification::send($superadmins, new ProjectUnapprovedEscalationNotification($project));
                }
            }
        });

    return self::SUCCESS;
})->purpose('Send project approval reminders and superadmin escalations');

Schedule::command('model:prune', ['--model' => [SecurityViolation::class]])
    ->daily();

Schedule::command('erag:sync-disposable-email-list')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('projects:alert-stalled')
    ->daily()
    ->withoutOverlapping();

Schedule::command('projects:alert-overdue')
    ->daily()
    ->withoutOverlapping();

Schedule::command('projects:alert-unapproved')
    ->daily()
    ->withoutOverlapping();
