<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('reuses the same staged entry id across repeated staged syncs until the state file is cleared', function (): void {
    $repo = fakeGitRepo();

    file_put_contents($repo['path'].'/first.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'first.php']);

    runUpdateChangelog($repo['path'], '20260328190000');

    $firstRun = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($firstRun)
        ->toContain('<!-- changelog:auto:start:staged-20260328190000 -->')
        ->toContain('### Commit updates')
        ->toContain('- added `first.php`');

    runInRepo($repo['path'], ['git', 'reset', '--', 'first.php']);
    unlink($repo['path'].'/first.php');

    file_put_contents($repo['path'].'/second.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'second.php']);

    runUpdateChangelog($repo['path'], '20260328190005');

    $secondRun = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect(substr_count($secondRun, '<!-- changelog:auto:start:staged-'))->toBe(1)
        ->and($secondRun)->toContain('<!-- changelog:auto:start:staged-20260328190000 -->')
        ->and($secondRun)->toContain('- added `second.php`')
        ->and($secondRun)->not->toContain('- added `first.php`');
});

it('appends a new staged entry after the hook state file is cleared', function (): void {
    $repo = fakeGitRepo();

    file_put_contents($repo['path'].'/alpha.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'alpha.php']);

    runUpdateChangelog($repo['path'], '20260328191000');

    unlink($repo['state_file']);

    file_put_contents($repo['path'].'/beta.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'beta.php']);

    runUpdateChangelog($repo['path'], '20260328191500');

    $markdown = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($markdown)
        ->toContain('<!-- changelog:auto:start:staged-20260328191000 -->')
        ->toContain('<!-- changelog:auto:start:staged-20260328191500 -->')
        ->toContain('- added `alpha.php`')
        ->toContain('- added `beta.php`');
});

/**
 * @return array{path: string, state_file: string}
 */
function fakeGitRepo(): array
{
    $path = sys_get_temp_dir().'/tenanto-changelog-'.bin2hex(random_bytes(8));
    mkdir($path, 0777, true);

    runInRepo($path, ['git', 'init', '--quiet']);
    runInRepo($path, ['git', 'config', 'user.name', 'Codex']);
    runInRepo($path, ['git', 'config', 'user.email', 'codex@example.com']);

    file_put_contents($path.'/CHANGELOG.md', "# Changelog\n");
    runInRepo($path, ['git', 'add', 'CHANGELOG.md']);
    runInRepo($path, ['git', 'commit', '--quiet', '-m', 'chore: bootstrap changelog repo']);

    return [
        'path' => $path,
        'state_file' => $path.'/.git/tenanto-changelog-entry-id',
    ];
}

function runUpdateChangelog(string $repoPath, string $timestamp): void
{
    $process = new Process([
        PHP_BINARY,
        base_path('scripts/update_changelog.php'),
        '--mode=staged',
        '--repo-root='.$repoPath,
        '--changelog=CHANGELOG.md',
        '--state-file=.git/tenanto-changelog-entry-id',
        '--date=2026-03-28',
        '--timestamp='.$timestamp,
    ], base_path());

    $process->mustRun();
}

/**
 * @param  list<string>  $command
 */
function runInRepo(string $repoPath, array $command): void
{
    $process = new Process($command, $repoPath);
    $process->mustRun();
}
