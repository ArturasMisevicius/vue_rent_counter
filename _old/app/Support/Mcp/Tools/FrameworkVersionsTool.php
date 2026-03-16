<?php

declare(strict_types=1);

namespace App\Support\Mcp\Tools;

use Composer\InstalledVersions;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Returns currently installed Laravel, Filament, MCP, Tailwind, and related stack versions for this codebase.')]
class FrameworkVersionsTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'php' => PHP_VERSION,
            'backend' => [
                'laravel/framework' => $this->composerVersion('laravel/framework'),
                'filament/filament' => $this->composerVersion('filament/filament'),
                'laravel/mcp' => $this->composerVersion('laravel/mcp'),
                'livewire/livewire' => $this->composerVersion('livewire/livewire'),
                'laravel/sanctum' => $this->composerVersion('laravel/sanctum'),
                'pestphp/pest' => $this->composerVersion('pestphp/pest'),
                'phpunit/phpunit' => $this->composerVersion('phpunit/phpunit'),
            ],
            'frontend' => $this->frontendVersions(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    private function composerVersion(string $package): ?string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package);
    }

    /**
     * @return array<string, string|null>
     */
    private function frontendVersions(): array
    {
        $path = base_path('package.json');

        if (! File::exists($path)) {
            return [];
        }

        /** @var array{
         *   dependencies?: array<string, string>,
         *   devDependencies?: array<string, string>
         * } $packageJson
         */
        $packageJson = json_decode(
            File::get($path),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return [
            'tailwindcss' => $packageJson['devDependencies']['tailwindcss'] ?? null,
            'vite' => $packageJson['devDependencies']['vite'] ?? null,
            'laravel-vite-plugin' => $packageJson['devDependencies']['laravel-vite-plugin'] ?? null,
            'alpinejs' => $packageJson['dependencies']['alpinejs'] ?? null,
        ];
    }
}
