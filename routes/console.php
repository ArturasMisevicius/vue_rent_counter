<?php

use App\Models\SecurityViolation;
use App\Services\Operations\BackupRestoreReadinessService;
use App\Services\Operations\PhaseOneGuardrailsBranchProtectionService;
use App\Services\Operations\ReleaseReadinessEvidenceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('model:prune', ['--model' => [SecurityViolation::class]])
    ->daily();

Schedule::command('erag:sync-disposable-email-list')
    ->dailyAt('03:30')
    ->withoutOverlapping();
