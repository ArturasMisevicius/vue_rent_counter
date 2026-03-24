<?php

declare(strict_types=1);

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FormRequestScenarioFactory;
use Tests\TestCase;

use function PHPUnit\Framework\assertContains;

uses(TestCase::class, RefreshDatabase::class);

it('keeps every request under app http requests covered by the request test matrix', function (): void {
    $requestClasses = collect(discoverFormRequestClasses())
        ->sort()
        ->values()
        ->all();

    $context = FormRequestScenarioFactory::context();

    $scenarioClasses = collect(FormRequestScenarioFactory::scenarios())
        ->map(static fn (array $scenario): string => $scenario['request']($context)::class)
        ->sort()
        ->values()
        ->all();

    expect($scenarioClasses)->toBe($requestClasses);
});

it('ensures each form request defines the required validation contract methods', function (string $requestClass): void {
    $reflection = new ReflectionClass($requestClass);

    expect($reflection->isSubclassOf(FormRequest::class))->toBeTrue();

    assertContains(InteractsWithValidationPayload::class, $reflection->getTraitNames());

    foreach (['authorize', 'rules', 'messages', 'attributes', 'prepareForValidation'] as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
    }
})->with(fn (): array => collect(discoverFormRequestClasses())
    ->map(static fn (string $requestClass): array => [$requestClass])
    ->all());

/**
 * @return list<class-string<FormRequest>>
 */
function discoverFormRequestClasses(): array
{
    $requestsPath = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Requests';

    return collect((new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($requestsPath),
    )))
        ->filter(static fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
        ->reject(static fn (SplFileInfo $file): bool => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Concerns'.DIRECTORY_SEPARATOR))
        ->map(static function (SplFileInfo $file): string {
            $relativePath = str($file->getPathname())
                ->after(dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR)
                ->replace(DIRECTORY_SEPARATOR, '\\')
                ->replace('.php', '');

            return 'App\\'.$relativePath;
        })
        ->sort()
        ->values()
        ->all();
}
