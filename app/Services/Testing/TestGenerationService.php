<?php

declare(strict_types=1);

namespace App\Services\Testing;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Service for generating tests based on application classes
 */
class TestGenerationService
{
    public function __construct(
        private ClassAnalyzer $analyzer,
        private TemplateRenderer $renderer
    ) {}

    /**
     * Generate tests for a given class
     */
    public function generateTests(string $className, string $type): array
    {
        $analysis = $this->analyzer->analyze($className);
        
        $requirements = $this->determineRequirements($analysis, $type);
        
        $requirements = $this->applyTenancyRules($requirements);
        
        $requirements = $this->applySecurityRules($requirements);
        
        return $this->renderer->render($requirements);
    }

    /**
     * Determine test requirements based on class analysis
     */
    private function determineRequirements(array $analysis, string $type): array
    {
        $requirements = [
            'class' => $analysis['class'],
            'type' => $type,
            'tests' => [],
            'imports' => [],
            'traits' => ['RefreshDatabase'],
        ];

        // Add type-specific requirements
        match ($type) {
            'controller' => $this->addControllerRequirements($requirements, $analysis),
            'model' => $this->addModelRequirements($requirements, $analysis),
            'service' => $this->addServiceRequirements($requirements, $analysis),
            'filament' => $this->addFilamentRequirements($requirements, $analysis),
            'policy' => $this->addPolicyRequirements($requirements, $analysis),
            default => null,
        };

        return $requirements;
    }

    /**
     * Apply multi-tenancy rules to test requirements
     */
    private function applyTenancyRules(array $requirements): array
    {
        if ($this->usesBelongsToTenant($requirements['class'])) {
            $requirements['tests'][] = 'tenant_isolation';
            $requirements['tests'][] = 'cross_tenant_access_prevention';
            $requirements['imports'][] = 'App\Services\TenantContext';
        }

        if ($this->usesHierarchicalScope($requirements['class'])) {
            $requirements['tests'][] = 'hierarchical_scope';
            $requirements['tests'][] = 'manager_access_control';
        }

        return $requirements;
    }

    /**
     * Apply security rules to test requirements
     */
    private function applySecurityRules(array $requirements): array
    {
        $requirements['tests'][] = 'authorization';
        $requirements['tests'][] = 'input_validation';
        
        if ($requirements['type'] === 'controller') {
            $requirements['tests'][] = 'csrf_protection';
            $requirements['tests'][] = 'xss_prevention';
        }

        return $requirements;
    }

    /**
     * Add controller-specific requirements
     */
    private function addControllerRequirements(array &$requirements, array $analysis): void
    {
        $requirements['tests'] = array_merge($requirements['tests'], [
            'index',
            'show',
            'create',
            'update',
            'delete',
        ]);

        $requirements['imports'][] = 'Illuminate\Foundation\Testing\RefreshDatabase';
    }

    /**
     * Add model-specific requirements
     */
    private function addModelRequirements(array &$requirements, array $analysis): void
    {
        $requirements['tests'] = array_merge($requirements['tests'], [
            'factory_creation',
            'relationships',
            'scopes',
            'attributes',
            'soft_deletes',
        ]);

        if (isset($analysis['relationships'])) {
            $requirements['relationships'] = $analysis['relationships'];
        }
    }

    /**
     * Add service-specific requirements
     */
    private function addServiceRequirements(array &$requirements, array $analysis): void
    {
        $requirements['tests'] = array_merge($requirements['tests'], [
            'instantiation',
            'tenant_context',
            'error_handling',
            'validation',
            'transactions',
        ]);
    }

    /**
     * Add Filament resource-specific requirements
     */
    private function addFilamentRequirements(array &$requirements, array $analysis): void
    {
        $requirements['tests'] = array_merge($requirements['tests'], [
            'list_page',
            'create_page',
            'edit_page',
            'table_operations',
            'form_operations',
            'bulk_actions',
            'navigation',
        ]);

        $requirements['imports'][] = 'Livewire\Livewire';
    }

    /**
     * Add policy-specific requirements
     */
    private function addPolicyRequirements(array &$requirements, array $analysis): void
    {
        $requirements['tests'] = array_merge($requirements['tests'], [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
        ]);
    }

    /**
     * Check if class uses BelongsToTenant trait
     */
    private function usesBelongsToTenant(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $traits = class_uses_recursive($className);
        return in_array('App\Traits\BelongsToTenant', $traits);
    }

    /**
     * Check if class uses HierarchicalScope
     */
    private function usesHierarchicalScope(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);
            $bootMethod = $reflection->getMethod('boot');
            $source = file_get_contents($reflection->getFileName());
            
            return str_contains($source, 'HierarchicalScope');
        } catch (\Exception $e) {
            return false;
        }
    }
}
