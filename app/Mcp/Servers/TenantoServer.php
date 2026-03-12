<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\FilamentResourcesTool;
use App\Mcp\Tools\FrameworkVersionsTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Tenanto Server')]
#[Version('0.0.1')]
#[Instructions('Use this server for Laravel, Filament, and frontend stack introspection in the Tenanto application.')]
class TenantoServer extends Server
{
    protected array $tools = [
        FrameworkVersionsTool::class,
        FilamentResourcesTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
