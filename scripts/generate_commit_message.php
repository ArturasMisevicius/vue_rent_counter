#!/usr/bin/env php
<?php

declare(strict_types=1);

$options = getopt('', [
    'repo-root::',
    'subject::',
]);

$repoRoot = isset($options['repo-root'])
    ? resolvePath(getcwd() ?: dirname(__DIR__), (string) $options['repo-root'])
    : dirname(__DIR__);

$changes = parseNameStatusLines(stagedNameStatusLines($repoRoot));

if ($changes === []) {
    exit(0);
}

$subjectChanges = array_values(array_filter(
    $changes,
    static fn (array $change): bool => ! isChangelogChange($change),
));

if ($subjectChanges === []) {
    $subjectChanges = $changes;
}

$explicitSubject = trim((string) ($options['subject'] ?? ''));
$subject = $explicitSubject !== ''
    ? $explicitSubject
    : buildSubject($subjectChanges);

echo $subject."\n\n".buildBody($subjectChanges, $repoRoot)."\n";

/**
 * @return array<int, string>
 */
function stagedNameStatusLines(string $repoRoot): array
{
    $output = runGit($repoRoot, [
        'diff',
        '--cached',
        '--name-status',
        '--diff-filter=ACMRD',
    ]);

    $output = trim($output);

    if ($output === '') {
        return [];
    }

    return preg_split("/\r?\n/", $output) ?: [];
}

/**
 * @return array<int, array{kind: string, paths: array<int, string>}>
 */
function parseNameStatusLines(array $lines): array
{
    $changes = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = explode("\t", $line);
        $status = strtoupper((string) array_shift($parts));
        $kind = substr($status, 0, 1);

        if ($kind === 'R' && count($parts) >= 2) {
            $changes[] = [
                'kind' => 'R',
                'paths' => [$parts[0], $parts[1]],
            ];

            continue;
        }

        if ($kind === 'C' && count($parts) >= 2) {
            $changes[] = [
                'kind' => 'C',
                'paths' => [$parts[0], $parts[1]],
            ];

            continue;
        }

        $path = $parts[0] ?? null;

        if ($path === null || $path === '') {
            continue;
        }

        $changes[] = [
            'kind' => $kind,
            'paths' => [$path],
        ];
    }

    return $changes;
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 */
function buildSubject(array $changes): string
{
    $type = inferType($changes);
    $scope = inferScope($changes);
    $verb = match ($type) {
        'fix' => 'correct',
        default => 'update',
    };

    return sprintf('%s: %s %s', $type, $verb, $scope);
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 */
function buildBody(array $changes, string $repoRoot): string
{
    $lines = [
        'Generated from the staged git diff and summarized by product or engineering intent, not by file name.',
        '',
        'Change summary:',
        sprintf('- Scope: %s.', sentenceCase(inferScope($changes))),
    ];

    foreach (describeChangeIntents($changes) as $line) {
        $lines[] = '- '.$line.'.';
    }

    $shortStat = trim(runGit($repoRoot, ['diff', '--cached', '--shortstat']));

    if ($shortStat !== '') {
        $lines[] = '- Diff size: '.rtrim($shortStat, '.').'.';
    }

    return implode("\n", $lines);
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 * @return array<int, string>
 */
function describeChangeIntents(array $changes): array
{
    $groups = [
        'A' => [],
        'M' => [],
        'D' => [],
        'R' => [],
        'C' => [],
    ];

    foreach ($changes as $change) {
        $kind = normalizeChangeKind($change['kind']);
        $path = selectedIntentPath($change);

        $groups[$kind][] = inferIntentLabel($path);
    }

    $lines = [];

    foreach ($groups as $kind => $labels) {
        $labels = array_values(array_unique($labels));

        if ($labels === []) {
            continue;
        }

        $lines[] = match ($kind) {
            'A' => 'Added '.joinIntentLabels($labels),
            'D' => 'Removed '.joinIntentLabels($labels),
            'R' => 'Reorganized '.joinIntentLabels($labels),
            'C' => 'Reused '.joinIntentLabels($labels),
            default => 'Updated '.joinIntentLabels($labels),
        };
    }

    return $lines;
}

/**
 * @param  array{kind: string, paths: array<int, string>}  $change
 */
function selectedIntentPath(array $change): string
{
    if (in_array($change['kind'], ['R', 'C'], true)) {
        return $change['paths'][1] ?? $change['paths'][0];
    }

    return $change['paths'][0];
}

function normalizeChangeKind(string $kind): string
{
    return match ($kind) {
        'A', 'D', 'R', 'C' => $kind,
        default => 'M',
    };
}

/**
 * @param  array<int, string>  $labels
 */
function joinIntentLabels(array $labels): string
{
    if (count($labels) === 1) {
        return $labels[0];
    }

    $last = (string) array_pop($labels);

    if (count($labels) === 1) {
        return $labels[0].' and '.$last;
    }

    return implode(', ', $labels).', and '.$last;
}

function sentenceCase(string $value): string
{
    return ucfirst($value);
}

function inferIntentLabel(string $path): string
{
    if (str_starts_with($path, 'tests/')) {
        return inferDomainIntent($path).' coverage';
    }

    return inferDomainIntent($path);
}

function inferDomainIntent(string $path): string
{
    $rules = [
        'commit-message generation' => static fn (string $path): bool => str_contains($path, 'GenerateCommitMessage')
            || str_contains($path, 'generate_commit_message'),
        'commit-message enforcement' => static fn (string $path): bool => str_contains($path, 'commit-msg'),
        'pre-commit quality gates' => static fn (string $path): bool => str_contains($path, 'pre-commit'),
        'changelog automation' => static fn (string $path): bool => str_contains($path, 'update_changelog'),
        'git hook installation' => static fn (string $path): bool => str_contains($path, 'install-git-hooks'),
        'view hygiene guard' => static fn (string $path): bool => str_contains($path, 'ViewHygiene')
            || str_contains($path, 'check_view_hygiene'),
        'agent hook automation' => static fn (string $path): bool => str_starts_with($path, '.codex/hooks/'),
        'agent configuration' => static fn (string $path): bool => str_contains($path, 'hooks.json')
            || str_starts_with($path, '.agent/')
            || str_starts_with($path, '.agents/')
            || str_starts_with($path, '.ai/'),
        'documentation' => static fn (string $path): bool => isDocumentationPath($path),
        'localization behavior' => static fn (string $path): bool => str_starts_with($path, 'lang/')
            || preg_match('/Translation|Locale|Language/', $path) === 1,
        'billing period workflow' => static fn (string $path): bool => str_contains($path, 'BillingPeriod'),
        'billing workflow' => static fn (string $path): bool => preg_match('/Billing|Invoice|MeterReading|Payment/', $path) === 1,
        'tenant KYC workflow' => static fn (string $path): bool => preg_match('/TenantKyc|Kyc/', $path) === 1,
        'tenant document workflow' => static fn (string $path): bool => preg_match('/TenantDocument|Document/', $path) === 1,
        'move-out workflow' => static fn (string $path): bool => str_contains($path, 'MoveOut'),
        'lead workflow' => static fn (string $path): bool => preg_match('/ListingLead|Lead/', $path) === 1,
        'project collaboration' => static fn (string $path): bool => str_contains($path, 'Project'),
        'authorization behavior' => static fn (string $path): bool => preg_match('/Policy|Permission|Authorization|Security/', $path) === 1,
        'dashboard experience' => static fn (string $path): bool => str_contains($path, 'Dashboard'),
        'Filament admin workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Filament/'),
        'Livewire UI workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Livewire/'),
        'Blade interface templates' => static fn (string $path): bool => str_starts_with($path, 'resources/views/'),
        'frontend styling' => static fn (string $path): bool => str_starts_with($path, 'resources/css/')
            || str_ends_with($path, '.css')
            || str_contains($path, 'tailwind'),
        'database schema or seed data' => static fn (string $path): bool => str_starts_with($path, 'database/'),
        'route definitions' => static fn (string $path): bool => str_starts_with($path, 'routes/'),
        'application configuration' => static fn (string $path): bool => str_starts_with($path, 'config/'),
        'frontend build configuration' => static fn (string $path): bool => str_contains($path, 'package')
            || str_contains($path, 'vite')
            || str_contains($path, 'postcss'),
    ];

    foreach ($rules as $label => $matches) {
        if ($matches($path)) {
            return $label;
        }
    }

    return isApplicationPath($path) ? 'application behavior' : 'project automation';
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 */
function inferType(array $changes): string
{
    $paths = allPaths($changes);

    if (allPathsMatch($paths, static fn (string $path): bool => isDocumentationPath($path))) {
        return 'docs';
    }

    if (allPathsMatch($paths, static fn (string $path): bool => str_starts_with($path, 'tests/'))) {
        return 'test';
    }

    if (allPathsMatch($paths, static fn (string $path): bool => str_starts_with($path, 'lang/'))) {
        return 'fix';
    }

    if (hasPathMatch($paths, static fn (string $path): bool => isAutomationPath($path) || str_starts_with($path, '.agent/'))) {
        return 'chore';
    }

    if (hasPathMatch($paths, static fn (string $path): bool => isApplicationPath($path))) {
        return 'feat';
    }

    return 'chore';
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 */
function inferScope(array $changes): string
{
    $paths = allPaths($changes);

    $rules = [
        'git automation' => static fn (string $path): bool => isAutomationPath($path),
        'agent definitions' => static fn (string $path): bool => str_starts_with($path, '.agent/agents/'),
        'documentation' => static fn (string $path): bool => isDocumentationPath($path),
        'translations' => static fn (string $path): bool => str_starts_with($path, 'lang/'),
        'billing period workflow' => static fn (string $path): bool => str_contains($path, 'BillingPeriod'),
        'billing workflow' => static fn (string $path): bool => preg_match('/Billing|Invoice|MeterReading|Payment/', $path) === 1,
        'tenant KYC workflow' => static fn (string $path): bool => preg_match('/TenantKyc|Kyc/', $path) === 1,
        'tenant document workflow' => static fn (string $path): bool => preg_match('/TenantDocument|Document/', $path) === 1,
        'move-out workflow' => static fn (string $path): bool => str_contains($path, 'MoveOut'),
        'lead workflow' => static fn (string $path): bool => preg_match('/ListingLead|Lead/', $path) === 1,
        'project collaboration' => static fn (string $path): bool => str_contains($path, 'Project'),
        'routing' => static fn (string $path): bool => str_starts_with($path, 'routes/'),
        'Filament admin workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Filament/'),
        'Livewire UI workflow' => static fn (string $path): bool => str_starts_with($path, 'app/Livewire/'),
        'test coverage' => static fn (string $path): bool => str_starts_with($path, 'tests/'),
    ];

    foreach ($rules as $scope => $matches) {
        if (hasPathMatch($paths, $matches)) {
            return $scope;
        }
    }

    return 'project files';
}

/**
 * @param  array{kind: string, paths: array<int, string>}  $change
 */
function isChangelogChange(array $change): bool
{
    foreach ($change['paths'] as $path) {
        if ($path === 'CHANGELOG.md' || $path === 'changelog.md') {
            return true;
        }
    }

    return false;
}

function isDocumentationPath(string $path): bool
{
    return $path === 'README.md'
        || $path === 'CHANGELOG.md'
        || $path === 'changelog.md'
        || str_starts_with($path, 'docs/')
        || str_ends_with($path, '.md');
}

function isAutomationPath(string $path): bool
{
    return str_starts_with($path, '.codex/hooks/')
        || str_starts_with($path, '.githooks/')
        || $path === '.codex/hooks.json'
        || $path === 'scripts/generate_commit_message.php'
        || $path === 'scripts/update_changelog.php'
        || $path === 'scripts/install-git-hooks.sh';
}

function isApplicationPath(string $path): bool
{
    return str_starts_with($path, 'app/')
        || str_starts_with($path, 'database/')
        || str_starts_with($path, 'resources/')
        || str_starts_with($path, 'routes/')
        || str_starts_with($path, 'config/');
}

/**
 * @param  array<int, array{kind: string, paths: array<int, string>}>  $changes
 * @return array<int, string>
 */
function allPaths(array $changes): array
{
    $paths = [];

    foreach ($changes as $change) {
        foreach ($change['paths'] as $path) {
            $paths[] = $path;
        }
    }

    return array_values(array_unique($paths));
}

/**
 * @param  array<int, string>  $paths
 */
function allPathsMatch(array $paths, Closure $matches): bool
{
    if ($paths === []) {
        return false;
    }

    foreach ($paths as $path) {
        if (! $matches($path)) {
            return false;
        }
    }

    return true;
}

/**
 * @param  array<int, string>  $paths
 */
function hasPathMatch(array $paths, Closure $matches): bool
{
    foreach ($paths as $path) {
        if ($matches($path)) {
            return true;
        }
    }

    return false;
}

/**
 * @param  array<int, string>  $args
 */
function runGit(string $repoRoot, array $args): string
{
    $command = array_merge(['git', '-C', $repoRoot], $args);

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

    return (string) $stdout;
}

function resolvePath(string $repoRoot, string $path): string
{
    if (str_starts_with($path, '/')) {
        return $path;
    }

    return $repoRoot.'/'.$path;
}
