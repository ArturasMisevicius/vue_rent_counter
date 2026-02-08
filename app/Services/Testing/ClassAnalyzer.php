<?php

declare(strict_types=1);

namespace App\Services\Testing;

use ReflectionClass;
use ReflectionMethod;

/**
 * Analyzes PHP classes to extract information for test generation
 */
class ClassAnalyzer
{
    /**
     * Analyze a class and extract relevant information
     */
    public function analyze(string $className): array
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class {$className} does not exist");
        }

        $reflection = new ReflectionClass($className);

        return [
            'class' => $className,
            'shortName' => $reflection->getShortName(),
            'namespace' => $reflection->getNamespaceName(),
            'methods' => $this->extractMethods($reflection),
            'properties' => $this->extractProperties($reflection),
            'traits' => $this->extractTraits($reflection),
            'interfaces' => $this->extractInterfaces($reflection),
            'relationships' => $this->extractRelationships($reflection),
            'attributes' => $this->extractAttributes($reflection),
        ];
    }

    /**
     * Extract public methods from class
     */
    private function extractMethods(ReflectionClass $reflection): array
    {
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) {
                continue; // Skip inherited methods
            }

            if (str_starts_with($method->getName(), '__')) {
                continue; // Skip magic methods
            }

            $methods[] = [
                'name' => $method->getName(),
                'parameters' => $this->extractParameters($method),
                'returnType' => $method->getReturnType()?->getName(),
            ];
        }

        return $methods;
    }

    /**
     * Extract method parameters
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $parameters = [];

        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(),
                'type' => $parameter->getType()?->getName(),
                'hasDefault' => $parameter->isDefaultValueAvailable(),
                'default' => $parameter->isDefaultValueAvailable() 
                    ? $parameter->getDefaultValue() 
                    : null,
            ];
        }

        return $parameters;
    }

    /**
     * Extract class properties
     */
    private function extractProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->class !== $reflection->getName()) {
                continue;
            }

            $properties[] = [
                'name' => $property->getName(),
                'type' => $property->getType()?->getName(),
                'visibility' => $this->getPropertyVisibility($property),
            ];
        }

        return $properties;
    }

    /**
     * Get property visibility
     */
    private function getPropertyVisibility(\ReflectionProperty $property): string
    {
        if ($property->isPublic()) {
            return 'public';
        }
        if ($property->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Extract traits used by class
     */
    private function extractTraits(ReflectionClass $reflection): array
    {
        return array_keys($reflection->getTraits());
    }

    /**
     * Extract interfaces implemented by class
     */
    private function extractInterfaces(ReflectionClass $reflection): array
    {
        return array_keys($reflection->getInterfaces());
    }

    /**
     * Extract Eloquent relationships from model
     */
    private function extractRelationships(ReflectionClass $reflection): array
    {
        if (!$reflection->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
            return [];
        }

        $relationships = [];
        $source = file_get_contents($reflection->getFileName());

        // Look for common relationship methods
        $relationshipTypes = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany',
            'hasOneThrough', 'hasManyThrough', 'morphTo',
            'morphOne', 'morphMany', 'morphToMany', 'morphedByMany'
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) {
                continue;
            }

            $methodSource = $this->getMethodSource($source, $method->getName());
            
            foreach ($relationshipTypes as $type) {
                if (str_contains($methodSource, "return \$this->{$type}(")) {
                    $relationships[] = [
                        'name' => $method->getName(),
                        'type' => $type,
                    ];
                    break;
                }
            }
        }

        return $relationships;
    }

    /**
     * Extract model attributes and casts
     */
    private function extractAttributes(ReflectionClass $reflection): array
    {
        if (!$reflection->isSubclassOf('Illuminate\Database\Eloquent\Model')) {
            return [];
        }

        $attributes = [];
        $source = file_get_contents($reflection->getFileName());

        // Extract casts property
        if (preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $source, $matches)) {
            $castsString = $matches[1];
            preg_match_all("/'([^']+)'\s*=>\s*'([^']+)'/", $castsString, $castMatches);
            
            foreach ($castMatches[1] as $index => $attribute) {
                $attributes[$attribute] = [
                    'cast' => $castMatches[2][$index],
                ];
            }
        }

        return $attributes;
    }

    /**
     * Get source code for a specific method
     */
    private function getMethodSource(string $classSource, string $methodName): string
    {
        $pattern = "/function\s+{$methodName}\s*\([^)]*\)[^{]*\{(.*?)\n\s*\}/s";
        
        if (preg_match($pattern, $classSource, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
