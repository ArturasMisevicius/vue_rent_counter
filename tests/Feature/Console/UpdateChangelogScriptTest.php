<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('reuses the same staged entry id across repeated staged syncs until the state file is cleared', function (): void {
    $repo = fakeGitRepo();

    mkdir($repo['path'].'/app/Filament', 0777, true);
    file_put_contents($repo['path'].'/app/Filament/First.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'app/Filament/First.php']);

    runUpdateChangelog($repo['path'], '20260328190000');

    $firstRun = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($firstRun)
        ->toContain('<!-- changelog:auto:start:staged-20260328190000 -->')
        ->toContain('### Commit updates')
        ->toContain('- Added: Filament admin workflow.');

    runInRepo($repo['path'], ['git', 'reset', '--', 'app/Filament/First.php']);
    unlink($repo['path'].'/app/Filament/First.php');

    mkdir($repo['path'].'/docs', 0777, true);
    file_put_contents($repo['path'].'/docs/second.md', "# Second\n");
    runInRepo($repo['path'], ['git', 'add', 'docs/second.md']);

    runUpdateChangelog($repo['path'], '20260328190005');

    $secondRun = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect(substr_count($secondRun, '<!-- changelog:auto:start:staged-'))->toBe(1)
        ->and($secondRun)->toContain('<!-- changelog:auto:start:staged-20260328190000 -->')
        ->and($secondRun)->toContain('- Added: documentation.')
        ->and($secondRun)->not->toContain('- Added: Filament admin workflow.')
        ->and($secondRun)->not->toContain('docs/second.md');
});

it('appends a new staged entry after the hook state file is cleared', function (): void {
    $repo = fakeGitRepo();

    mkdir($repo['path'].'/app/Livewire', 0777, true);
    file_put_contents($repo['path'].'/app/Livewire/Alpha.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'app/Livewire/Alpha.php']);

    runUpdateChangelog($repo['path'], '20260328191000');

    unlink($repo['state_file']);

    mkdir($repo['path'].'/config', 0777, true);
    file_put_contents($repo['path'].'/config/beta.php', "<?php\n");
    runInRepo($repo['path'], ['git', 'add', 'config/beta.php']);

    runUpdateChangelog($repo['path'], '20260328191500');

    $markdown = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($markdown)
        ->toContain('<!-- changelog:auto:start:staged-20260328191000 -->')
        ->toContain('<!-- changelog:auto:start:staged-20260328191500 -->')
        ->toContain('- Added: Livewire UI workflow.')
        ->toContain('- Added: Livewire UI workflow and application configuration.')
        ->not->toContain('app/Livewire/Alpha.php')
        ->not->toContain('config/beta.php');
});

it('can write staged changelog entries in Russian', function (): void {
    $repo = fakeGitRepo();

    mkdir($repo['path'].'/.githooks', 0777, true);
    file_put_contents($repo['path'].'/.githooks/commit-msg', "#!/bin/sh\n");
    runInRepo($repo['path'], ['git', 'add', '.githooks/commit-msg']);

    runUpdateChangelog($repo['path'], '20260328192000', 'ru');

    $markdown = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($markdown)
        ->toContain('### Изменения Codex')
        ->toContain('- Добавлено: проверка commit message.')
        ->not->toContain('.githooks/commit-msg');
});

it('can write pending changelog entries in Russian', function (): void {
    $repo = fakeGitRepo();

    mkdir($repo['path'].'/docs', 0777, true);
    file_put_contents($repo['path'].'/docs/pending.md', "# Pending\n");
    runInRepo($repo['path'], ['git', 'add', 'docs/pending.md']);

    $process = new Process([
        PHP_BINARY,
        base_path('scripts/update_changelog.php'),
        '--mode=pending',
        '--repo-root='.$repo['path'],
        '--changelog=CHANGELOG.md',
        '--date=2026-03-28',
        '--timestamp=20260328193000',
        '--language=ru',
    ], base_path());

    $process->mustRun();

    $markdown = file_get_contents($repo['path'].'/CHANGELOG.md');

    expect($markdown)
        ->toContain('### Ожидающие staged-изменения')
        ->toContain('- Добавлено: документация.')
        ->not->toContain('docs/pending.md');
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

function runUpdateChangelog(string $repoPath, string $timestamp, string $language = 'en'): void
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
        '--language='.$language,
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
