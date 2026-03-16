<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

test('docker compose provisions mindsdb with mcp-ready http access', function (): void {
    $dockerCompose = Yaml::parseFile(base_path('docker-compose.yml'));

    expect(data_get($dockerCompose, 'services.mindsdb.image'))->toBe('mindsdb/mindsdb:latest');
    expect(data_get($dockerCompose, 'services.mindsdb.environment'))->toContain('MINDSDB_APIS=http,mysql');
    expect(data_get($dockerCompose, 'services.mindsdb.ports'))->toContain('47334:47334', '47335:47335');
    expect(data_get($dockerCompose, 'services.mindsdb.volumes'))->toContain('mindsdb:/root/mdb_storage');
    expect(array_key_exists('mindsdb', data_get($dockerCompose, 'volumes', [])))->toBeTrue();
});

test('project mcp clients register the migrated local endpoints and workspace paths', function (): void {
    $cursorConfig = json_decode(file_get_contents(base_path('.cursor/mcp.json')), true, 512, JSON_THROW_ON_ERROR);
    $aiConfig = json_decode(file_get_contents(base_path('.ai/mcp/mcp.json')), true, 512, JSON_THROW_ON_ERROR);
    $codexConfig = file_get_contents(base_path('.codex/config.toml'));
    $workspacePath = base_path();

    expect(data_get($cursorConfig, 'mcpServers.gptr-mcp.cwd'))->toBe($workspacePath);
    expect(data_get($cursorConfig, 'mcpServers.context7.command'))->toBe('npx');
    expect(data_get($cursorConfig, 'mcpServers.mindsdb.url'))->toBe('http://127.0.0.1:47334/mcp/sse');
    expect(data_get($aiConfig, 'mcpServers.gptr-mcp.cwd'))->toBe($workspacePath);
    expect(data_get($aiConfig, 'mcpServers.context7.command'))->toBe('npx');
    expect(data_get($aiConfig, 'mcpServers.mindsdb.url'))->toBe('http://127.0.0.1:47334/mcp/sse');
    expect($codexConfig)
        ->toContain('[mcp_servers.laravel-boost]')
        ->toContain('[mcp_servers.gptr-mcp]')
        ->toContain('[mcp_servers.context7]')
        ->toContain('[mcp_servers.mindsdb]')
        ->toContain('[mcp_servers.serena]')
        ->toContain('[mcp_servers.herd]')
        ->toContain('cwd = "'.$workspacePath.'"')
        ->not->toContain('/Users/andrejprus/Herd/my-store')
        ->not->toContain('C:\\Dropbox\\projects\\tenanto');
});

test('gpt researcher mcp support files and env wiring are present', function (): void {
    $servicesConfig = file_get_contents(base_path('config/services.php'));
    $envExample = file_get_contents(base_path('.env.example'));
    $scriptPath = base_path('scripts/gptr-mcp.sh');

    expect($servicesConfig)
        ->toContain("'gpt_researcher_mcp' => [")
        ->toContain("'repository' => env('GPT_RESEARCHER_MCP_REPOSITORY'")
        ->toContain("'branch' => env('GPT_RESEARCHER_MCP_BRANCH'")
        ->toContain("'path' => env('GPT_RESEARCHER_MCP_PATH'")
        ->toContain("'python' => env('GPT_RESEARCHER_MCP_PYTHON'")
        ->toContain("'transport' => env('GPT_RESEARCHER_MCP_TRANSPORT'");

    expect($envExample)
        ->toContain('OPENAI_API_KEY=')
        ->toContain('TAVILY_API_KEY=')
        ->toContain('GPT_RESEARCHER_MCP_REPOSITORY=')
        ->toContain('GPT_RESEARCHER_MCP_BRANCH=')
        ->toContain('GPT_RESEARCHER_MCP_PATH=')
        ->toContain('GPT_RESEARCHER_MCP_PYTHON=')
        ->toContain('GPT_RESEARCHER_MCP_TRANSPORT=');

    expect(file_exists(base_path('app/Console/Commands/InstallGptResearcherMcpCommand.php')))->toBeTrue();
    expect(file_exists(base_path('app/Actions/Mcp/InstallGptResearcherMcpAction.php')))->toBeTrue();
    expect(file_exists($scriptPath))->toBeTrue();
    expect(is_executable($scriptPath))->toBeTrue();
    expect(file_get_contents($scriptPath))
        ->toContain('php artisan gptr-mcp:install --no-interaction')
        ->toContain('OPENAI_API_KEY is not set.');
});

test('boost skill references resolve to installed project skill directories', function (): void {
    $boost = json_decode(file_get_contents(base_path('boost.json')), true, 512, JSON_THROW_ON_ERROR);

    $installedSkills = collect([
        ...glob(base_path('.ai/skills/*'), GLOB_ONLYDIR) ?: [],
        ...glob(base_path('.agents/skills/*'), GLOB_ONLYDIR) ?: [],
    ])->map(static fn (string $path): string => basename($path))->unique()->values()->all();

    $missingSkills = collect(data_get($boost, 'skills', []))
        ->diff($installedSkills)
        ->values()
        ->all();

    expect($missingSkills)->toBe([]);
});
