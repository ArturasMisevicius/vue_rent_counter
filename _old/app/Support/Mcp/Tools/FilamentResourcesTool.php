<?php

declare(strict_types=1);

namespace App\Support\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Returns discovered Filament resources and panel providers in the current application.')]
class FilamentResourcesTool extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'resources' => $this->discoverPhpClasses(
                directory: app_path('Filament/Resources'),
                baseNamespace: 'App\\Filament\\Resources',
            ),
            'panels' => $this->discoverPhpClasses(
                directory: app_path('Providers/Filament'),
                baseNamespace: 'App\\Providers\\Filament',
            ),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function discoverPhpClasses(string $directory, string $baseNamespace): array
    {
        if (! File::isDirectory($directory)) {
            return [];
        }

        $classes = collect(File::allFiles($directory))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(function ($file) use ($baseNamespace): string {
                $classPath = str_replace(['/', '\\'], '\\', $file->getRelativePathname());

                return sprintf('%s\\%s', $baseNamespace, Str::beforeLast($classPath, '.php'));
            })
            ->sort()
            ->values()
            ->all();

        return $classes;
    }
}
