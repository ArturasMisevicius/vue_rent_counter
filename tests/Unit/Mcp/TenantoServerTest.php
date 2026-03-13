<?php

declare(strict_types=1);

use App\Mcp\Servers\TenantoServer;
use App\Mcp\Tools\FilamentResourcesTool;
use App\Mcp\Tools\FrameworkVersionsTool;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Contracts\Transport;

it('registers expected MCP tools on the server', function () {
    $server = new TenantoServer(new class implements Transport
    {
        public function onReceive(Closure $handler): void {}

        public function run() {}

        public function send(string $message, ?string $sessionId = null): void {}

        public function sessionId(): ?string
        {
            return null;
        }

        public function stream(Closure $stream): void {}
    });
    $reflection = new ReflectionClass($server);
    $toolsProperty = $reflection->getProperty('tools');
    $toolsProperty->setAccessible(true);

    /** @var array<int, class-string> $tools */
    $tools = $toolsProperty->getValue($server);

    expect($tools)->toContain(FrameworkVersionsTool::class, FilamentResourcesTool::class);
});

it('registers the tenanto MCP handle', function () {
    expect(Mcp::getLocalServer('tenanto'))->not->toBeNull();
});
