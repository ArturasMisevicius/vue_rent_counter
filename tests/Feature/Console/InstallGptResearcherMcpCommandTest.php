<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->repositoryPath = storage_path('framework/testing/gptr-mcp-'.Str::uuid());
    $this->versionCommand = [
        '/usr/bin/python3',
        '-c',
        'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")',
    ];

    config()->set('services.gpt_researcher_mcp.path', $this->repositoryPath);
    config()->set('services.gpt_researcher_mcp.repository', 'https://github.com/assafelovic/gptr-mcp.git');
    config()->set('services.gpt_researcher_mcp.branch', 'master');
    config()->set('services.gpt_researcher_mcp.python', '/usr/bin/python3');
});

afterEach(function (): void {
    if (isset($this->repositoryPath)) {
        File::deleteDirectory($this->repositoryPath);
    }
});

it('clones the GPT Researcher repository and installs its dependencies', function (): void {
    Process::fake(function ($process) {
        if ($process->command === $this->versionCommand) {
            return Process::result('3.11');
        }

        if ($process->command === [
            'git',
            'clone',
            '--branch',
            'master',
            '--depth',
            '1',
            'https://github.com/assafelovic/gptr-mcp.git',
            $this->repositoryPath,
        ]) {
            File::ensureDirectoryExists($this->repositoryPath);
            File::put($this->repositoryPath.'/requirements.txt', implode(PHP_EOL, [
                'gpt-researcher>=0.14.0',
                'fastmcp>=2.8.0',
            ]).PHP_EOL);
            File::put($this->repositoryPath.'/server.py', 'print("ok")');
        }

        return Process::result();
    });

    $this->artisan('gptr-mcp:install')
        ->expectsOutputToContain('GPT Researcher MCP server is ready')
        ->assertSuccessful();

    Process::assertRan(function ($process): bool {
        return $process->command === [
            'git',
            'clone',
            '--branch',
            'master',
            '--depth',
            '1',
            'https://github.com/assafelovic/gptr-mcp.git',
            $this->repositoryPath,
        ];
    });

    Process::assertRan(function ($process): bool {
        return $process->command === [
            '/usr/bin/python3',
            '-m',
            'venv',
            $this->repositoryPath.'/.venv',
        ] && $process->path === $this->repositoryPath;
    });

    Process::assertRan(function ($process): bool {
        return $process->command === [
            $this->repositoryPath.'/.venv/bin/pip',
            'install',
            'git+https://github.com/assafelovic/gpt-researcher.git@main',
        ] && $process->path === $this->repositoryPath;
    });

    Process::assertRan(function ($process): bool {
        return $process->command === [
            $this->repositoryPath.'/.venv/bin/pip',
            'install',
            '-r',
            $this->repositoryPath.'/.laravel-gptr-mcp-requirements.txt',
        ] && $process->path === $this->repositoryPath;
    });
});

it('refreshes an existing git checkout when requested', function (): void {
    File::ensureDirectoryExists($this->repositoryPath.'/.git');
    File::put($this->repositoryPath.'/server.py', 'print("ok")');
    File::put($this->repositoryPath.'/requirements.txt', "gpt-researcher>=0.14.0\nfastmcp");

    Process::fake(function ($process) {
        if ($process->command === $this->versionCommand) {
            return Process::result('3.11');
        }

        return Process::result();
    });

    $this->artisan('gptr-mcp:install --refresh')->assertSuccessful();

    Process::assertRan(function ($process): bool {
        return $process->command === [
            'git',
            'pull',
            '--ff-only',
            'origin',
            'master',
        ] && $process->path === $this->repositoryPath;
    });

    Process::assertNotRan(function ($process): bool {
        return is_array($process->command)
            && ($process->command[0] ?? null) === 'git'
            && ($process->command[1] ?? null) === 'clone';
    });
});

it('fails to refresh a non git installation', function (): void {
    File::ensureDirectoryExists($this->repositoryPath);
    File::put($this->repositoryPath.'/server.py', 'print("ok")');
    File::put($this->repositoryPath.'/requirements.txt', "gpt-researcher>=0.14.0\nfastmcp");

    Process::fake();

    $this->artisan('gptr-mcp:install --refresh')
        ->expectsOutputToContain('cannot be refreshed because it is not a git checkout')
        ->assertFailed();

    Process::assertNotRan(function ($process): bool {
        return is_array($process->command)
            && ($process->command[0] ?? null) === 'git'
            && ($process->command[1] ?? null) === 'pull';
    });
});

it('fails with a clear message when Python is older than 3 point 11', function (): void {
    File::ensureDirectoryExists($this->repositoryPath);
    File::put($this->repositoryPath.'/server.py', 'print("ok")');
    File::put($this->repositoryPath.'/requirements.txt', "gpt-researcher>=0.14.0\nfastmcp");

    Process::fake(function ($process) {
        if ($process->command === $this->versionCommand) {
            return Process::result('3.9');
        }

        return Process::result();
    });

    $this->artisan('gptr-mcp:install')
        ->expectsOutputToContain('Python 3.11 or newer')
        ->assertFailed();
});
