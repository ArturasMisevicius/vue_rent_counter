<?php

declare(strict_types=1);

use App\Mcp\Tools\FrameworkVersionsTool;
use Laravel\Mcp\Request;

it('returns framework and frontend version payload', function () {
    $response = app(FrameworkVersionsTool::class)->handle(new Request);

    /** @var array<string, mixed> $payload */
    $payload = json_decode((string) $response->content(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKeys(['php', 'backend', 'frontend'])
        ->and($payload['backend']['laravel/framework'])->not->toBeNull()
        ->and($payload['backend']['filament/filament'])->not->toBeNull()
        ->and($payload['backend']['laravel/mcp'])->not->toBeNull()
        ->and($payload['frontend'])->toBeArray();
});
