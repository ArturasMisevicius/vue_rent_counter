<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

it('resets planning artifacts before initializing a new project context', function (): void {
    $sandbox = storage_path('framework/testing/init-command-'.Str::uuid());

    File::ensureDirectoryExists($sandbox.'/.planning/research');
    File::put($sandbox.'/.planning/PROJECT.md', '# project');
    File::put($sandbox.'/.planning/ROADMAP.md', '# roadmap');
    File::put($sandbox.'/.planning/REQUIREMENTS.md', '# requirements');
    File::put($sandbox.'/.planning/STATE.md', '# state');
    File::put($sandbox.'/.planning/config.json', '{}');
    File::put($sandbox.'/package.json', '{}');

    try {
        $command = [
            'node',
            base_path('.codex/get-shit-done/bin/gsd-tools.cjs'),
            'init',
            'new-project',
            '--reset',
            '--cwd',
            $sandbox,
            '--raw',
        ];

        $firstRun = new Process($command, base_path());
        $firstRun->mustRun();

        $firstPayload = json_decode($firstRun->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        expect(File::exists($sandbox.'/.planning'))->toBeFalse()
            ->and($firstPayload['project_exists'])->toBeFalse()
            ->and($firstPayload['planning_exists'])->toBeFalse()
            ->and($firstPayload['has_package_file'])->toBeTrue()
            ->and($firstPayload['is_brownfield'])->toBeTrue();

        $secondRun = new Process($command, base_path());
        $secondRun->mustRun();

        $secondPayload = json_decode($secondRun->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        expect(File::exists($sandbox.'/.planning'))->toBeFalse()
            ->and($secondPayload['project_exists'])->toBeFalse()
            ->and($secondPayload['planning_exists'])->toBeFalse();
    } finally {
        File::deleteDirectory($sandbox);
    }
});

it('registers init command wrappers across supported command surfaces', function (): void {
    $paths = [
        '.agent/workflows/init.md',
        '.claude/commands/init.md',
        '.codex/commands/init.md',
        '.gemini/commands/init.toml',
    ];

    foreach ($paths as $relativePath) {
        $absolutePath = base_path($relativePath);
        $contents = file_get_contents($absolutePath);

        expect($contents)
            ->not->toBeFalse()
            ->and((string) $contents)->toContain('new-project')
            ->and((string) $contents)->toContain('.planning');
    }
});
