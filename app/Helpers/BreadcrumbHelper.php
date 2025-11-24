<?php

namespace App\Helpers;

class BreadcrumbHelper
{
    /**
     * Generate breadcrumbs based on the current route.
     *
     * @return array
     */
    public static function generate(): array
    {
        $routeName = request()->route()?->getName();
        $breadcrumbs = [];

        if (!$routeName) {
            return $breadcrumbs;
        }

        // Get the role prefix (admin, manager, tenant)
        $parts = explode('.', $routeName);
        $role = $parts[0] ?? null;

        // Managers: suppress breadcrumbs per requirement
        if ($role === 'manager') {
            return [];
        }

        // Always start with dashboard
        if ($role && in_array($role, ['admin', 'manager', 'tenant'])) {
            $breadcrumbs[] = [
                'label' => 'Dashboard',
                'url' => route("{$role}.dashboard"),
                'active' => $routeName === "{$role}.dashboard",
            ];
        }

        // If we're on the dashboard, return early
        if ($routeName === "{$role}.dashboard") {
            return $breadcrumbs;
        }

        // Handle nested resources (e.g., invoices.items.show)
        if (count($parts) >= 3) {
            $resource = $parts[1] ?? null;
            
            // Check for nested resources (e.g., properties.meters, invoices.items)
            if (count($parts) >= 4 && $parts[2] !== 'index' && $parts[2] !== 'create' && $parts[2] !== 'show' && $parts[2] !== 'edit' && $parts[2] !== 'destroy') {
                // This is a nested resource pattern like invoices/{invoice}/items
                $parentResource = $parts[1];
                $nestedResource = $parts[2];
                $action = $parts[3] ?? 'index';
                
                // Add parent resource breadcrumb
                $parentLabel = ucfirst(str_replace('-', ' ', $parentResource));
                $parentIndexRoute = "{$role}.{$parentResource}.index";
                if (\Route::has($parentIndexRoute)) {
                    $breadcrumbs[] = [
                        'label' => $parentLabel,
                        'url' => route($parentIndexRoute),
                        'active' => false,
                    ];
                }
                
                // Add parent instance breadcrumb
                $parentModel = request()->route()->parameters()[$parentResource] ?? null;
                if ($parentModel) {
                    $parentDisplayName = self::getModelDisplayName($parentModel);
                    $parentShowRoute = "{$role}.{$parentResource}.show";
                    $breadcrumbs[] = [
                        'label' => $parentDisplayName,
                        'url' => \Route::has($parentShowRoute) ? route($parentShowRoute, $parentModel) : null,
                        'active' => false,
                    ];
                }
                
                // Add nested resource breadcrumb
                $nestedLabel = ucfirst(str_replace('-', ' ', $nestedResource));
                $nestedIndexRoute = "{$role}.{$parentResource}.{$nestedResource}.index";
                if (\Route::has($nestedIndexRoute) && $parentModel) {
                    $breadcrumbs[] = [
                        'label' => $nestedLabel,
                        'url' => route($nestedIndexRoute, $parentModel),
                        'active' => $action === 'index',
                    ];
                }
                
                // Add nested action breadcrumb if not index
                if ($action !== 'index') {
                    $actionLabel = match($action) {
                        'create' => 'Create',
                        'edit' => 'Edit',
                        'show' => 'View',
                        default => ucfirst($action),
                    };
                    
                    $breadcrumbs[] = [
                        'label' => $actionLabel,
                        'url' => null,
                        'active' => true,
                    ];
                }
                
                return $breadcrumbs;
            }
            
            // Regular resource with action
            $action = $parts[2] ?? 'index';

            // Add resource list breadcrumb
            if ($resource && $resource !== 'dashboard') {
                $resourceLabel = ucfirst(str_replace('-', ' ', $resource));
                
                // Check if the index route exists
                $indexRoute = "{$role}.{$resource}.index";
                if (\Route::has($indexRoute)) {
                    $breadcrumbs[] = [
                        'label' => $resourceLabel,
                        'url' => route($indexRoute),
                        'active' => $action === 'index',
                    ];
                }

                // Add action breadcrumb if not index
                if ($action !== 'index') {
                    $actionLabel = match($action) {
                        'create' => 'Create',
                        'edit' => 'Edit',
                        'show' => 'View',
                        default => ucfirst($action),
                    };

                    // For show/edit, try to get the model name
                    $parameters = request()->route()->parameters();
                    
                    // Try different parameter name variations
                    $singularResource = \Illuminate\Support\Str::singular($resource);
                    $underscoreResource = str_replace('-', '_', $resource);
                    $singularUnderscoreResource = str_replace('-', '_', $singularResource);
                    
                    $model = $parameters[$resource] 
                        ?? $parameters[$singularResource] 
                        ?? $parameters[$underscoreResource] 
                        ?? $parameters[$singularUnderscoreResource] 
                        ?? null;
                    
                    if ($model && is_object($model) && in_array($action, ['show', 'edit'])) {
                        // Try to get a display name from the model
                        $displayName = self::getModelDisplayName($model);
                        $actionLabel = $displayName;
                    }

                    $breadcrumbs[] = [
                        'label' => $actionLabel,
                        'url' => null,
                        'active' => true,
                    ];
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Get a display name for a model.
     *
     * @param mixed $model
     * @return string
     */
    protected static function getModelDisplayName($model): string
    {
        if (is_object($model)) {
            // Try common display name attributes
            if (isset($model->name) && !empty($model->name)) {
                return $model->name;
            }
            if (isset($model->title) && !empty($model->title)) {
                return $model->title;
            }
            if (isset($model->address) && !empty($model->address)) {
                return $model->address;
            }
            if (isset($model->serial_number) && !empty($model->serial_number)) {
                return $model->serial_number;
            }
            if (isset($model->id) && !empty($model->id)) {
                return "#{$model->id}";
            }
        }

        return 'Details';
    }
}
