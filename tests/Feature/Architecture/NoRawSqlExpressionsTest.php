<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

it('keeps application code free from raw SQL expression helpers', function () {
    $forbiddenPatterns = [
        'DB::raw(',
        'DB::select(',
        'DB::statement(',
        'DB::unprepared(',
        'selectRaw(',
        'whereRaw(',
        'orWhereRaw(',
        'orderByRaw(',
        'groupByRaw(',
        'havingRaw(',
        'fromRaw(',
    ];

    $violations = applicationFilesForRawSqlInspection()
        ->flatMap(function (SplFileInfo $file) use ($forbiddenPatterns): array {
            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                return [];
            }

            return collect($forbiddenPatterns)
                ->filter(fn (string $pattern): bool => str_contains($contents, $pattern))
                ->map(fn (string $pattern): string => sprintf(
                    '%s contains %s',
                    Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR),
                    $pattern,
                ))
                ->all();
        })
        ->values();

    expect($violations)->toBeEmpty();
});

/**
 * @return Collection<int, SplFileInfo>
 */
function applicationFilesForRawSqlInspection(): Collection
{
    return collect([
        app_path(),
        base_path('routes'),
        resource_path('views'),
    ])->flatMap(fn (string $path): array => File::isDirectory($path) ? File::allFiles($path) : []);
}
