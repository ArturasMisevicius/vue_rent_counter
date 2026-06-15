<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('generates an English commit message from staged git automation changes', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/.codex/hooks', 0777, true);
    mkdir($repo.'/scripts', 0777, true);

    file_put_contents($repo.'/.codex/hooks/auto-changelog-commit-push.sh', "#!/bin/sh\n");
    file_put_contents($repo.'/scripts/generate_commit_message.php', "<?php\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);

    $message = runGenerateCommitMessage($repo);

    expect($message)
        ->toStartWith('chore: update git automation')
        ->toContain('Generated from the staged git diff and summarized by product or engineering intent, not by file name.')
        ->toContain('Change summary:')
        ->toContain('Added agent hook automation and commit-message generation.')
        ->not->toContain('Included changes:')
        ->not->toContain('.codex/hooks/auto-changelog-commit-push.sh')
        ->not->toContain('scripts/generate_commit_message.php')
        ->not->toMatch('/[А-Яа-яЁё]/u');
});

it('infers a billing period workflow subject without listing staged file names', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/app/Filament/Resources/BillingPeriods/Pages', 0777, true);
    mkdir($repo.'/tests/Feature/Billing', 0777, true);

    file_put_contents($repo.'/app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php', "<?php\n");
    file_put_contents($repo.'/tests/Feature/Billing/BillingPeriodWorkflowTest.php', "<?php\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);

    $message = runGenerateCommitMessage($repo);

    expect($message)
        ->toStartWith('feat: update billing period workflow')
        ->toContain('Added billing period workflow and billing period workflow coverage.')
        ->not->toContain('app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php')
        ->not->toContain('tests/Feature/Billing/BillingPeriodWorkflowTest.php');
});

it('keeps an explicit subject but still writes an English staged diff body', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/docs', 0777, true);
    file_put_contents($repo.'/docs/example.md', "# Example\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);

    $message = runGenerateCommitMessage($repo, 'docs: clarify setup guide');

    expect($message)
        ->toStartWith('docs: clarify setup guide')
        ->toContain('Added documentation.')
        ->not->toContain('docs/example.md')
        ->not->toMatch('/[А-Яа-яЁё]/u');
});

it('summarizes edited and removed functionality instead of file paths', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/docs', 0777, true);
    file_put_contents($repo.'/docs/obsolete.md', "# Old\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);
    runInCommitMessageRepo($repo, ['git', 'commit', '--quiet', '-m', 'docs: add old docs']);

    file_put_contents($repo.'/README.md', "# Updated\n");
    unlink($repo.'/docs/obsolete.md');

    runInCommitMessageRepo($repo, ['git', 'add', '-A']);

    $message = runGenerateCommitMessage($repo);

    expect($message)
        ->toStartWith('docs: update documentation')
        ->toContain('Updated documentation.')
        ->toContain('Removed documentation.')
        ->not->toContain('README.md')
        ->not->toContain('docs/obsolete.md')
        ->not->toMatch('/[А-Яа-яЁё]/u');
});

it('fills empty template commit messages without overriding manual messages', function (): void {
    $repo = fakeCommitMessageGitRepo();

    installPrepareCommitMessageHook($repo);

    mkdir($repo.'/app/Livewire', 0777, true);
    file_put_contents($repo.'/app/Livewire/DashboardPanel.php', "<?php\n");

    runInCommitMessageRepo($repo, ['git', 'add', 'app/Livewire/DashboardPanel.php']);

    $emptyMessage = $repo.'/.git/COMMIT_EDITMSG';
    file_put_contents($emptyMessage, "# Template only\n");

    runPrepareCommitMessageHook($repo, $emptyMessage, 'template');

    expect(file_get_contents($emptyMessage))
        ->toStartWith('feat: update Livewire UI workflow')
        ->toContain('Added dashboard experience.')
        ->not->toContain('app/Livewire/DashboardPanel.php');

    $manualMessage = $repo.'/.git/MANUAL_COMMIT_EDITMSG';
    file_put_contents($manualMessage, "fix: keep manual message\n");

    runPrepareCommitMessageHook($repo, $manualMessage, 'template');

    expect(file_get_contents($manualMessage))->toBe("fix: keep manual message\n");
});

function fakeCommitMessageGitRepo(): string
{
    $path = sys_get_temp_dir().'/tenanto-commit-message-'.bin2hex(random_bytes(8));
    mkdir($path, 0777, true);

    runInCommitMessageRepo($path, ['git', 'init', '--quiet']);
    runInCommitMessageRepo($path, ['git', 'config', 'user.name', 'Codex']);
    runInCommitMessageRepo($path, ['git', 'config', 'user.email', 'codex@example.com']);

    file_put_contents($path.'/README.md', "# Test\n");
    runInCommitMessageRepo($path, ['git', 'add', 'README.md']);
    runInCommitMessageRepo($path, ['git', 'commit', '--quiet', '-m', 'chore: bootstrap test repo']);

    return $path;
}

function runGenerateCommitMessage(string $repoPath, ?string $subject = null): string
{
    $command = [
        PHP_BINARY,
        base_path('scripts/generate_commit_message.php'),
        '--repo-root='.$repoPath,
    ];

    if ($subject !== null) {
        $command[] = '--subject='.$subject;
    }

    $process = new Process($command, base_path());
    $process->mustRun();

    return $process->getOutput();
}

function installPrepareCommitMessageHook(string $repoPath): void
{
    mkdir($repoPath.'/.githooks', 0777, true);
    mkdir($repoPath.'/scripts', 0777, true);

    copy(base_path('.githooks/prepare-commit-msg'), $repoPath.'/.githooks/prepare-commit-msg');
    copy(base_path('scripts/generate_commit_message.php'), $repoPath.'/scripts/generate_commit_message.php');

    chmod($repoPath.'/.githooks/prepare-commit-msg', 0755);
}

function runPrepareCommitMessageHook(string $repoPath, string $messagePath, string $source): void
{
    $process = new Process([
        $repoPath.'/.githooks/prepare-commit-msg',
        $messagePath,
        $source,
    ], $repoPath);

    $process->mustRun();
}

/**
 * @param  list<string>  $command
 */
function runInCommitMessageRepo(string $repoPath, array $command): void
{
    $process = new Process($command, $repoPath);
    $process->mustRun();
}
