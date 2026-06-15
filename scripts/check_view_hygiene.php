<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/**
 * @return list<string>
 */
function filesMatching(string $directory, callable $matches): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();

        if ($matches($path)) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $paths
 * @return list<array{path: string, line: int, token: string}>
 */
function forbiddenTokenHits(array $paths, array $tokens): array
{
    $hits = [];

    foreach ($paths as $path) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            continue;
        }

        foreach ($lines as $index => $line) {
            foreach ($tokens as $token) {
                if (str_contains($line, $token)) {
                    $hits[] = [
                        'path' => $path,
                        'line' => $index + 1,
                        'token' => $token,
                    ];
                }
            }
        }
    }

    return $hits;
}

/**
 * @param  list<string>  $paths
 * @return list<array{path: string, line: int, token: string}>
 */
function forbiddenPatternHits(array $paths, array $patterns): array
{
    $hits = [];

    foreach ($paths as $path) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            continue;
        }

        foreach ($lines as $index => $line) {
            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $hits[] = [
                        'path' => $path,
                        'line' => $index + 1,
                        'token' => is_string($label) ? $label : $pattern,
                    ];
                }
            }
        }
    }

    return $hits;
}

function relativePath(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
}

$bladeFiles = filesMatching(
    $root.'/resources/views',
    fn (string $path): bool => str_ends_with($path, '.blade.php'),
);

$stylePreprocessorFiles = filesMatching(
    $root.'/resources',
    fn (string $path): bool => (bool) preg_match('/\.(scss|sass|less)$/i', $path),
);

$styleReferenceFiles = array_values(array_filter([
    $root.'/package.json',
    $root.'/vite.config.js',
    $root.'/vite.config.mjs',
    $root.'/vite.config.ts',
], 'is_file'));

$bladeHits = [
    ...forbiddenTokenHits($bladeFiles, ['@php', '@endphp']),
    ...forbiddenPatternHits($bladeFiles, ['raw PHP' => '/<\?php\b/']),
];

$styleReferenceHits = forbiddenPatternHits($styleReferenceFiles, [
    'stylesheet preprocessor reference' => '/(?:\.scss|\.sass|\.less|resources\/scss|resources\/sass|\bsass\b)/i',
]);

$hasFailures = $bladeHits !== [] || $stylePreprocessorFiles !== [] || $styleReferenceHits !== [];

if (! $hasFailures) {
    fwrite(STDOUT, "View hygiene guard passed: Blade has no @php/raw PHP and frontend uses CSS-only styling.\n");

    exit(0);
}

fwrite(STDERR, "View hygiene guard failed.\n\n");

if ($bladeHits !== []) {
    fwrite(STDERR, "Blade templates must not contain @php, @endphp, or raw PHP blocks:\n");

    foreach ($bladeHits as $hit) {
        fwrite(STDERR, sprintf(
            "  - %s:%d (%s)\n",
            relativePath($root, $hit['path']),
            $hit['line'],
            $hit['token'],
        ));
    }

    fwrite(STDERR, "\n");
}

if ($stylePreprocessorFiles !== []) {
    fwrite(STDERR, "Stylesheet preprocessors are not allowed; use resources/css/*.css only:\n");

    foreach ($stylePreprocessorFiles as $path) {
        fwrite(STDERR, sprintf("  - %s\n", relativePath($root, $path)));
    }

    fwrite(STDERR, "\n");
}

if ($styleReferenceHits !== []) {
    fwrite(STDERR, "Frontend config must not reference SCSS/Sass/Less:\n");

    foreach ($styleReferenceHits as $hit) {
        fwrite(STDERR, sprintf(
            "  - %s:%d (%s)\n",
            relativePath($root, $hit['path']),
            $hit['line'],
            $hit['token'],
        ));
    }

    fwrite(STDERR, "\n");
}

exit(1);
