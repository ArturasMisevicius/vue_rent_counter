<?php

declare(strict_types=1);

use App\Filament\Support\Changelog\GitChangelogUpdater;

it('formats staged name-status lines into readable changelog bullets', function () {
    $updater = new GitChangelogUpdater;

    expect($updater->formatNameStatusLines([
        "M\tapp/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php",
        "A\t.githooks/pre-commit",
        "R100\told/path.php\tnew/path.php",
        "M\tCHANGELOG.md",
        '',
    ]))->toBe([
        '- Added: pre-commit quality gates.',
        '- Updated: Filament admin workflow.',
        '- Reorganized: project automation.',
    ]);
});

it('formats staged name-status lines into Russian changelog bullets', function () {
    $updater = new GitChangelogUpdater;

    expect($updater->formatNameStatusLines([
        "M\tapp/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php",
        "A\t.codex/hooks/auto-changelog-commit-push.sh",
        "D\told/file.php",
        "R100\told/path.php\tnew/path.php",
        "M\tCHANGELOG.md",
        '',
    ], 'ru'))->toBe([
        '- Добавлено: автоматизация agent hooks.',
        '- Обновлено: Filament admin workflow.',
        '- Удалено: автоматизация проекта.',
        '- Перестроено: автоматизация проекта.',
    ]);
});

it('creates todays changelog section with a pending entry block', function () {
    $updater = new GitChangelogUpdater;

    $updated = $updater->sync(
        "# Changelog\n\n## 2026-03-27\n\n### Existing change\n\n- Updated: documentation.\n",
        '2026-03-28',
        'pending',
        'Pending staged changes',
        ['- Updated: changelog automation.'],
    );

    expect($updated)
        ->toContain("## 2026-03-28\n\n<!-- changelog:auto:start:pending -->")
        ->toContain("### Pending staged changes\n\n- Updated: changelog automation.")
        ->toContain('## 2026-03-27');
});

it('does not confuse a titled date heading with the exact daily section', function () {
    $updater = new GitChangelogUpdater;

    $updated = $updater->sync(
        "# Changelog\n\n## 2026-06-15 Documentation Reconstruction\n\n### Current Product State\n\n- Existing reconstructed history.\n",
        '2026-06-15',
        'pending',
        'Ожидающие staged-изменения',
        ['- Обновлено: документация.'],
    );

    expect($updated)
        ->toContain("## 2026-06-15\n\n<!-- changelog:auto:start:pending -->")
        ->toContain('## 2026-06-15 Documentation Reconstruction')
        ->toContain('- Обновлено: документация.');
});

it('replaces the existing pending block on repeated pre-commit runs', function () {
    $updater = new GitChangelogUpdater;

    $markdown = <<<'MD'
# Changelog

## 2026-03-28

<!-- changelog:auto:start:pending -->
### Pending staged changes

- Updated: application behavior.
<!-- changelog:auto:end:pending -->

### Existing change

- Updated: documentation.
MD;

    $updated = $updater->sync(
        $markdown,
        '2026-03-28',
        'pending',
        'Pending staged changes',
        ['- Updated: application behavior.'],
    );

    expect(substr_count($updated, '<!-- changelog:auto:start:pending -->'))->toBe(1)
        ->and($updated)->toContain('- Updated: application behavior.')
        ->and($updated)->not->toContain('- Updated: project automation.');
});

it('finalizes a pending block into a titled commit entry', function () {
    $updater = new GitChangelogUpdater;

    $markdown = <<<'MD'
# Changelog

## 2026-03-28

<!-- changelog:auto:start:pending -->
### Pending staged changes

- Updated: application behavior.
<!-- changelog:auto:end:pending -->
MD;

    $updated = $updater->sync(
        $markdown,
        '2026-03-28',
        'commit-20260328180000',
        'feat: automate changelog updates before commit',
        ['- Updated: application behavior.'],
        'pending',
    );

    expect($updated)
        ->toContain('<!-- changelog:auto:start:commit-20260328180000 -->')
        ->toContain('### feat: automate changelog updates before commit')
        ->not->toContain('<!-- changelog:auto:start:pending -->');
});
