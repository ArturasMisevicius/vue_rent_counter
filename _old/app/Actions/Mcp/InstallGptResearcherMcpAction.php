<?php

declare(strict_types=1);

namespace App\Actions\Mcp;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class InstallGptResearcherMcpAction
{
    public function __construct(
        protected Filesystem $files,
    ) {}

    public function handle(bool $refresh = false): string
    {
        $repositoryPath = $this->repositoryPath();

        $this->files->ensureDirectoryExists(dirname($repositoryPath));

        if (! $this->isInstalled($repositoryPath)) {
            $this->guardAgainstUnexpectedDirectory($repositoryPath);
            $this->cloneRepository($repositoryPath);
        } elseif ($refresh) {
            $this->updateRepository($repositoryPath);
        }

        $this->installDependencies($repositoryPath);

        return $repositoryPath;
    }

    protected function cloneRepository(string $repositoryPath): void
    {
        $result = Process::timeout(120)->run([
            'git',
            'clone',
            '--branch',
            $this->repositoryBranch(),
            '--depth',
            '1',
            $this->repositoryUrl(),
            $repositoryPath,
        ]);

        $this->ensureSuccessful($result, 'Unable to clone the GPT Researcher MCP repository.');
    }

    protected function updateRepository(string $repositoryPath): void
    {
        if (! $this->files->isDirectory($repositoryPath.DIRECTORY_SEPARATOR.'.git')) {
            throw new RuntimeException('The installed GPT Researcher MCP server cannot be refreshed because it is not a git checkout.');
        }

        $result = Process::path($repositoryPath)->timeout(120)->run([
            'git',
            'pull',
            '--ff-only',
            'origin',
            $this->repositoryBranch(),
        ]);

        $this->ensureSuccessful($result, 'Unable to refresh the GPT Researcher MCP repository.');
    }

    protected function installDependencies(string $repositoryPath): void
    {
        $this->ensureSupportedPythonVersion();

        $virtualEnvironmentPath = $repositoryPath.DIRECTORY_SEPARATOR.'.venv';
        $pipBinary = $this->pipBinary($virtualEnvironmentPath);
        $filteredRequirementsPath = $this->filteredRequirementsPath($repositoryPath);

        if (! $this->files->exists($pipBinary)) {
            $result = Process::path($repositoryPath)->timeout(180)->run([
                $this->pythonBinary(),
                '-m',
                'venv',
                $virtualEnvironmentPath,
            ]);

            $this->ensureSuccessful($result, 'Unable to create the GPT Researcher MCP virtual environment.');
        }

        $result = Process::path($repositoryPath)
            ->timeout(600)
            ->env(['PIP_DISABLE_PIP_VERSION_CHECK' => '1'])
            ->run([
                $pipBinary,
                'install',
                'git+https://github.com/assafelovic/gpt-researcher.git@main',
            ]);

        $this->ensureSuccessful($result, 'Unable to install GPT Researcher from GitHub.');

        $result = Process::path($repositoryPath)
            ->timeout(600)
            ->env(['PIP_DISABLE_PIP_VERSION_CHECK' => '1'])
            ->run([
                $pipBinary,
                'install',
                '-r',
                $filteredRequirementsPath,
            ]);

        $this->ensureSuccessful($result, 'Unable to install GPT Researcher MCP dependencies.');
    }

    protected function isInstalled(string $repositoryPath): bool
    {
        return $this->files->exists($repositoryPath.DIRECTORY_SEPARATOR.'server.py')
            && $this->files->exists($repositoryPath.DIRECTORY_SEPARATOR.'requirements.txt');
    }

    protected function ensureSupportedPythonVersion(): void
    {
        $result = Process::timeout(30)->run([
            $this->pythonBinary(),
            '-c',
            'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")',
        ]);

        $this->ensureSuccessful($result, 'Unable to determine the Python version for GPT Researcher MCP.');

        $version = trim((string) $result->output());

        if ($version !== '' && version_compare($version, '3.11', '<')) {
            throw new RuntimeException("GPT Researcher MCP requires Python 3.11 or newer. [{$this->pythonBinary()}] resolved to [{$version}]. Set GPT_RESEARCHER_MCP_PYTHON in [.env] to a Python 3.11+ binary.");
        }
    }

    protected function guardAgainstUnexpectedDirectory(string $repositoryPath): void
    {
        if (! $this->files->isDirectory($repositoryPath)) {
            return;
        }

        $entries = array_values(array_diff(scandir($repositoryPath) ?: [], ['.', '..']));

        if ($entries !== []) {
            throw new RuntimeException("Cannot install GPT Researcher MCP into [{$repositoryPath}] because the directory already contains files.");
        }
    }

    protected function filteredRequirementsPath(string $repositoryPath): string
    {
        $requirementsPath = $repositoryPath.DIRECTORY_SEPARATOR.'requirements.txt';

        if (! $this->files->exists($requirementsPath)) {
            throw new RuntimeException("The GPT Researcher MCP checkout at [{$repositoryPath}] is missing requirements.txt.");
        }

        $filteredRequirementsPath = $repositoryPath.DIRECTORY_SEPARATOR.'.laravel-gptr-mcp-requirements.txt';
        $requirements = file($requirementsPath, FILE_IGNORE_NEW_LINES) ?: [];
        $filteredRequirements = array_filter($requirements, function (string $line): bool {
            return ! str_starts_with(ltrim($line), 'gpt-researcher');
        });

        $this->files->put(
            $filteredRequirementsPath,
            implode(PHP_EOL, $filteredRequirements).PHP_EOL,
        );

        return $filteredRequirementsPath;
    }

    protected function ensureSuccessful(object $result, string $fallbackMessage): void
    {
        if (! method_exists($result, 'failed') || ! $result->failed()) {
            return;
        }

        $errorOutput = trim((string) $result->errorOutput());
        $output = trim((string) $result->output());

        throw new RuntimeException($errorOutput !== '' ? $errorOutput : ($output !== '' ? $output : $fallbackMessage));
    }

    protected function repositoryUrl(): string
    {
        return (string) config('services.gpt_researcher_mcp.repository', 'https://github.com/assafelovic/gptr-mcp.git');
    }

    protected function repositoryBranch(): string
    {
        return (string) config('services.gpt_researcher_mcp.branch', 'master');
    }

    protected function repositoryPath(): string
    {
        return (string) config('services.gpt_researcher_mcp.path', storage_path('app/mcp/gptr-mcp'));
    }

    protected function pythonBinary(): string
    {
        return (string) config('services.gpt_researcher_mcp.python', 'python3');
    }

    protected function pipBinary(string $virtualEnvironmentPath): string
    {
        $binaryDirectory = PHP_OS_FAMILY === 'Windows' ? 'Scripts' : 'bin';
        $binaryName = PHP_OS_FAMILY === 'Windows' ? 'pip.exe' : 'pip';

        return $virtualEnvironmentPath.DIRECTORY_SEPARATOR.$binaryDirectory.DIRECTORY_SEPARATOR.$binaryName;
    }
}
