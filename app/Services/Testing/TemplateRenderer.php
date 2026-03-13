<?php

declare(strict_types=1);

namespace App\Services\Testing;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders test templates with provided data
 */
class TemplateRenderer
{
    /**
     * Render a test template with requirements
     */
    public function render(array $requirements): array
    {
        $templatePath = $this->getTemplatePath($requirements['type']);
        
        if (!File::exists($templatePath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        $template = File::get($templatePath);
        
        $rendered = $this->replaceVariables($template, $requirements);
        
        $outputPath = $this->determineOutputPath($requirements);
        
        return [
            'content' => $rendered,
            'path' => $outputPath,
            'type' => $requirements['type'],
        ];
    }

    /**
     * Get template path for test type
     */
    private function getTemplatePath(string $type): string
    {
        $templates = config('generate-tests-easy.templates', []);
        
        if (isset($templates[$type]) && File::exists($templates[$type])) {
            return $templates[$type];
        }

        return base_path("tests/stubs/{$type}.test.stub");
    }

    /**
     * Replace template variables with actual values
     */
    private function replaceVariables(string $template, array $requirements): string
    {
        $variables = $this->extractVariables($requirements);
        
        foreach ($variables as $key => $value) {
            $template = str_replace("{{ {$key} }}", $value, $template);
        }

        return $template;
    }

    /**
     * Extract variables from requirements
     */
    private function extractVariables(array $requirements): array
    {
        $className = $requirements['class'];
        $shortName = class_basename($className);
        
        $variables = [
            'namespace' => $this->getTestNamespace($requirements['type']),
            'className' => $className,
            'shortName' => $shortName,
            'modelName' => $this->extractModelName($className),
            'resourceName' => Str::camel($this->extractModelName($className)),
            'resourceNamePlural' => Str::plural(Str::camel($this->extractModelName($className))),
            'tableName' => Str::snake(Str::plural($this->extractModelName($className))),
            'controllerName' => $shortName,
            'serviceName' => $shortName,
            'policyName' => $shortName,
            'middlewareName' => $shortName,
            'observerName' => $shortName,
            'valueObjectName' => $shortName,
            'routePrefix' => Str::kebab(Str::plural($this->extractModelName($className))),
        ];

        // Add relationship placeholders
        if (isset($requirements['relationships'])) {
            $variables['relationships'] = $this->formatRelationships($requirements['relationships']);
        } else {
            $variables['relationships'] = '// No relationships defined';
        }

        // Add attribute cast placeholders
        if (isset($requirements['attributes'])) {
            $variables['attributeCasts'] = $this->formatAttributeCasts($requirements['attributes']);
        } else {
            $variables['attributeCasts'] = '// No attribute casts defined';
        }

        // Add form fields placeholder
        $variables['formFields'] = $this->generateFormFields($requirements);

        return $variables;
    }

    /**
     * Extract model name from class name
     */
    private function extractModelName(string $className): string
    {
        $shortName = class_basename($className);
        
        // Remove common suffixes
        $suffixes = ['Controller', 'Service', 'Policy', 'Resource', 'Middleware', 'Observer'];
        
        foreach ($suffixes as $suffix) {
            if (str_ends_with($shortName, $suffix)) {
                return str_replace($suffix, '', $shortName);
            }
        }

        return $shortName;
    }

    /**
     * Get test namespace for type
     */
    private function getTestNamespace(string $type): string
    {
        $namespaces = config('generate-tests-easy.namespaces', []);
        
        return $namespaces[$type] ?? 'Tests\\Feature';
    }

    /**
     * Determine output path for generated test
     */
    private function determineOutputPath(array $requirements): string
    {
        $type = $requirements['type'];
        $className = $requirements['class'];
        $shortName = class_basename($className);
        
        $paths = config('generate-tests-easy.paths', []);
        $basePath = $paths[$type] ?? 'Feature';
        
        $testName = $shortName . 'Test.php';
        
        return base_path("tests/{$basePath}/{$testName}");
    }

    /**
     * Format relationships for template
     */
    private function formatRelationships(array $relationships): string
    {
        if (empty($relationships)) {
            return '// No relationships defined';
        }

        $lines = [];
        foreach ($relationships as $relationship) {
            $name = $relationship['name'];
            $lines[] = "expect(\${{ resourceName }}->{$name})->not->toBeNull();";
        }

        return implode("\n        ", $lines);
    }

    /**
     * Format attribute casts for template
     */
    private function formatAttributeCasts(array $attributes): string
    {
        if (empty($attributes)) {
            return '// No attribute casts defined';
        }

        $lines = [];
        foreach ($attributes as $name => $config) {
            $cast = $config['cast'];
            $assertion = $this->getAssertionForCast($cast);
            $lines[] = "expect(\${{ resourceName }}->{$name})->{$assertion};";
        }

        return implode("\n        ", $lines);
    }

    /**
     * Get appropriate assertion for cast type
     */
    private function getAssertionForCast(string $cast): string
    {
        return match ($cast) {
            'boolean', 'bool' => 'toBeBoolean()',
            'integer', 'int' => 'toBeInt()',
            'float', 'double', 'decimal' => 'toBeFloat()',
            'string' => 'toBeString()',
            'array', 'json' => 'toBeArray()',
            'datetime', 'date' => 'toBeInstanceOf(\Carbon\Carbon::class)',
            default => 'not->toBeNull()',
        };
    }

    /**
     * Generate form fields for template
     */
    private function generateFormFields(array $requirements): string
    {
        // This would be enhanced based on actual model analysis
        return "'description' => \$newData->description,\n                'is_active' => true,";
    }
}
