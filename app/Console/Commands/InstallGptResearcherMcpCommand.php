<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Mcp\InstallGptResearcherMcpAction;
use Illuminate\Console\Command;
use RuntimeException;

class InstallGptResearcherMcpCommand extends Command
{
    protected $signature = 'gptr-mcp:install
                            {--refresh : Pull the latest GPT Researcher MCP checkout before reinstalling dependencies.}';

    protected $description = 'Install or refresh the GPT Researcher MCP sidecar for local agent use';

    public function handle(InstallGptResearcherMcpAction $installGptResearcherMcp): int
    {
        try {
            $repositoryPath = $installGptResearcherMcp->handle((bool) $this->option('refresh'));
        } catch (RuntimeException $runtimeException) {
            $this->components->error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $this->components->info("GPT Researcher MCP server is ready at [{$repositoryPath}].");
        $this->components->info('Use [scripts/gptr-mcp.sh] as the MCP command in your local agent config.');

        if (blank(config('services.gpt_researcher_mcp.openai_api_key'))) {
            $this->components->warn('Set OPENAI_API_KEY in [.env] before starting the GPT Researcher MCP server.');
        }

        if (blank(config('services.gpt_researcher_mcp.tavily_api_key'))) {
            $this->components->warn('TAVILY_API_KEY is optional but recommended for higher quality research results.');
        }

        return self::SUCCESS;
    }
}
