<?php

declare(strict_types=1);

use App\Support\Changelog\GitChangelogUpdater;

it('formats staged name-status lines into readable changelog bullets', function () {
    $updater = new GitChangelogUpdater;

    expect($updater->formatNameStatusLines([
        "M\tapp/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php",
        "A\t.githooks/pre-commit",
        "R100\told/path.php\tnew/path.php",
        "M\tCHANGELOG.md",
        '',
    ]))->toBe([
        '- updated `app/Filament/Resources/Organizations/RelationManagers/UsersRelationManager.php`',
        '- added `.githooks/pre-commit`',
        '- renamed `old/path.php` to `new/path.php`',
    ]);
});

it('creates todays changelog section with a pending entry block', function () {
    $updater = new GitChangelogUpdater;

    $updated = $updater->sync(
        "# Changelog\n\n## 2026-03-27\n\n### Existing change\n\n- updated `README.md`\n",
        '2026-03-28',
        'pending',
        'Pending staged changes',
        ['- updated `app/Support/Changelog/GitChangelogUpdater.php`'],
    );

    expect($updated)
        ->toContain("## 2026-03-28\n\n<!-- changelog:auto:start:pending -->")
        ->toContain("### Pending staged changes\n\n- updated `app/Support/Changelog/GitChangelogUpdater.php`")
        ->toContain('## 2026-03-27');
});

it('replaces the existing pending block on repeated pre-commit runs', function () {
    $updater = new GitChangelogUpdater;

    $markdown = <<<'MD'
# Changelog

## 2026-03-28

<!-- changelog:auto:start:pending -->
### Pending staged changes

- updated `app/OldFile.php`
<!-- changelog:auto:end:pending -->

### Existing change

- updated `README.md`
MD;

    $updated = $updater->sync(
        $markdown,
        '2026-03-28',
        'pending',
        'Pending staged changes',
        ['- updated `app/NewFile.php`'],
    );

    expect(substr_count($updated, '<!-- changelog:auto:start:pending -->'))->toBe(1)
        ->and($updated)->toContain('- updated `app/NewFile.php`')
        ->and($updated)->not->toContain('- updated `app/OldFile.php`');
});

it('finalizes a pending block into a titled commit entry', function () {
    $updater = new GitChangelogUpdater;

    $markdown = <<<'MD'
# Changelog

## 2026-03-28

<!-- changelog:auto:start:pending -->
### Pending staged changes

- updated `app/NewFile.php`
<!-- changelog:auto:end:pending -->
MD;

    $updated = $updater->sync(
        $markdown,
        '2026-03-28',
        'commit-20260328180000',
        'feat: automate changelog updates before commit',
        ['- updated `app/NewFile.php`'],
        'pending',
    );

    expect($updated)
        ->toContain('<!-- changelog:auto:start:commit-20260328180000 -->')
        ->toContain('### feat: automate changelog updates before commit')
        ->not->toContain('<!-- changelog:auto:start:pending -->');
});
