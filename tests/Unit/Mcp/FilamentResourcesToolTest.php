<?php

declare(strict_types=1);

use App\Mcp\Tools\FilamentResourcesTool;
use Laravel\Mcp\Request;

it('returns discovered filament resources and panel providers', function () {
    $response = app(FilamentResourcesTool::class)->handle(new Request);

    /** @var array{resources: array<int, string>, panels: array<int, string>} $payload */
    $payload = json_decode((string) $response->content(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['resources'])->toContain('App\\Filament\\Resources\\UserResource')
        ->and($payload['panels'])->toContain('App\\Providers\\Filament\\AdminPanelProvider');
});
