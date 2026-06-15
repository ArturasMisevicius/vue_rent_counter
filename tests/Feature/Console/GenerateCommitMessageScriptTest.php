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
        ->toContain('Generated from the staged git diff')
        ->toContain('Included changes:')
        ->toContain('Added `.codex/hooks/auto-changelog-commit-push.sh`')
        ->toContain('Added `scripts/generate_commit_message.php`')
        ->not->toMatch('/[А-Яа-яЁё]/u');
});

it('infers a billing period workflow subject and lists every staged file', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/app/Filament/Resources/BillingPeriods/Pages', 0777, true);
    mkdir($repo.'/tests/Feature/Billing', 0777, true);

    file_put_contents($repo.'/app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php', "<?php\n");
    file_put_contents($repo.'/tests/Feature/Billing/BillingPeriodWorkflowTest.php', "<?php\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);

    $message = runGenerateCommitMessage($repo);

    expect($message)
        ->toStartWith('feat: update billing period workflow')
        ->toContain('Added `app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php`')
        ->toContain('Added `tests/Feature/Billing/BillingPeriodWorkflowTest.php`');
});

it('keeps an explicit subject but still writes an English staged diff body', function (): void {
    $repo = fakeCommitMessageGitRepo();

    mkdir($repo.'/docs', 0777, true);
    file_put_contents($repo.'/docs/example.md', "# Example\n");

    runInCommitMessageRepo($repo, ['git', 'add', '.']);

    $message = runGenerateCommitMessage($repo, 'docs: clarify setup guide');

    expect($message)
        ->toStartWith('docs: clarify setup guide')
        ->toContain('Added `docs/example.md`')
        ->not->toMatch('/[А-Яа-яЁё]/u');
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

/**
 * @param  list<string>  $command
 */
function runInCommitMessageRepo(string $repoPath, array $command): void
{
    $process = new Process($command, $repoPath);
    $process->mustRun();
}
