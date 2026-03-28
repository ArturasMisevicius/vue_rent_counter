#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Filament\Support\Changelog\GitChangelogUpdater;

require __DIR__.'/../vendor/autoload.php';

$options = getopt('', [
    'mode:',
    'changelog::',
    'message-file::',
    'date::',
    'repo-root::',
    'state-file::',
    'entry-id::',
    'timestamp::',
    'title::',
]);

$mode = $options['mode'] ?? 'pending';
$repoRoot = isset($options['repo-root'])
    ? resolvePath(getcwd() ?: dirname(__DIR__), (string) $options['repo-root'])
    : dirname(__DIR__);
$changelogPath = isset($options['changelog'])
    ? resolvePath($repoRoot, (string) $options['changelog'])
    : $repoRoot.'/CHANGELOG.md';
$date = $options['date'] ?? date('Y-m-d');
$timestamp = (string) ($options['timestamp'] ?? date('YmdHis'));
$updater = new GitChangelogUpdater;
$changes = $updater->formatNameStatusLines(stagedNameStatusLines($repoRoot));

if ($changes === []) {
    exit(0);
}

$markdown = file_exists($changelogPath)
    ? (string) file_get_contents($changelogPath)
    : "# Changelog\n";

$updated = match ($mode) {
    'staged' => $updater->sync(
        $markdown,
        $date,
        resolveStagedEntryId($options, $repoRoot, $timestamp),
        trim((string) ($options['title'] ?? '')) !== ''
            ? trim((string) $options['title'])
            : 'Commit updates',
        $changes,
    ),
    'pending' => $updater->sync(
        $markdown,
        $date,
        'pending',
        'Pending staged changes',
        $changes,
    ),
    'finalize' => $updater->sync(
        $markdown,
        $date,
        $updater->nextFinalEntryId($timestamp),
        resolveCommitTitle($options),
        $changes,
        'pending',
    ),
    default => throw new InvalidArgumentException(sprintf('Unsupported changelog update mode [%s].', $mode)),
};

if ($updated === $markdown) {
    exit(0);
}

file_put_contents($changelogPath, $updated);

function resolveStagedEntryId(array $options, string $repoRoot, string $timestamp): string
{
    $explicitEntryId = trim((string) ($options['entry-id'] ?? ''));

    if ($explicitEntryId !== '') {
        return $explicitEntryId;
    }

    $stateFile = resolveStateFile($options, $repoRoot);

    if ($stateFile !== null && is_file($stateFile)) {
        $existing = trim((string) file_get_contents($stateFile));

        if ($existing !== '') {
            return $existing;
        }
    }

    $entryId = 'staged-'.preg_replace('/[^0-9]/', '', $timestamp);

    if ($stateFile !== null) {
        $directory = dirname($stateFile);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($stateFile, $entryId);
    }

    return $entryId;
}

function resolveStateFile(array $options, string $repoRoot): ?string
{
    $stateFile = trim((string) ($options['state-file'] ?? ''));

    if ($stateFile === '') {
        return null;
    }

    return resolvePath($repoRoot, $stateFile);
}

function resolveCommitTitle(array $options): string
{
    $title = trim((string) ($options['title'] ?? ''));

    if ($title !== '') {
        return $title;
    }

    $messageFile = $options['message-file'] ?? null;

    if (! is_string($messageFile) || $messageFile === '') {
        return 'Commit updates';
    }

    $contents = @file_get_contents($messageFile);

    if (! is_string($contents)) {
        return 'Commit updates';
    }

    foreach (preg_split("/\r?\n/", $contents) as $line) {
        $line = trim((string) $line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        return $line;
    }

    return 'Commit updates';
}

/**
 * @return array<int, string>
 */
function stagedNameStatusLines(string $repoRoot): array
{
    $command = [
        'git',
        '-C',
        $repoRoot,
        'diff',
        '--cached',
        '--name-status',
        '--diff-filter=ACMRD',
    ];

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $repoRoot);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to inspect staged git changes.');
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException(sprintf(
            'Unable to inspect staged git changes: %s',
            trim((string) $stderr),
        ));
    }

    return preg_split("/\r?\n/", trim((string) $stdout)) ?: [];
}

function resolvePath(string $repoRoot, string $path): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return $repoRoot.'/'.$path;
}
