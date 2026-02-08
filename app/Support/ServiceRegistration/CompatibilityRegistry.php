<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

/**
 * Compatibility Registry for Filament v4 and Laravel 12 compatibility
 * 
 * Handles class aliases and compatibility shims for framework upgrades.
 * This registry ensures smooth transitions between major versions.
 */
final readonly class CompatibilityRegistry
{
    /**
     * Filament v4 class aliases for backward compatibility
     * 
     * @var array<string, string>
     */
    private const FILAMENT_ALIASES = [
        \Filament\Forms\Components\Section::class => \Filament\Schemas\Components\Section::class,
        \Filament\Tables\Actions\BulkActionGroup::class => \Filament\Actions\BulkActionGroup::class,
        \Filament\Tables\Actions\EditAction::class => \Filament\Actions\EditAction::class,
        \Filament\Tables\Actions\DeleteAction::class => \Filament\Actions\DeleteAction::class,
        \Filament\Tables\Actions\DeleteBulkAction::class => \Filament\Actions\DeleteBulkAction::class,
        \Filament\Tables\Actions\ViewAction::class => \Filament\Actions\ViewAction::class,
        \Filament\Tables\Actions\Action::class => \Filament\Actions\Action::class,
        \Filament\Tables\Actions\BulkAction::class => \Filament\Actions\BulkAction::class,
        \Filament\Tables\Actions\CreateAction::class => \Filament\Actions\CreateAction::class,
    ];

    /**
     * Register Filament v4 compatibility aliases
     */
    public function registerFilamentCompatibility(): void
    {
        foreach (self::FILAMENT_ALIASES as $oldClass => $newClass) {
            if (! class_exists($oldClass) && class_exists($newClass)) {
                class_alias($newClass, $oldClass);
            }
        }
    }

    /**
     * Register translation compatibility for backup package and Laravel 12
     */
    public function registerTranslationCompatibility(): void
    {
        // Load translations moved from lang/vendor to lang/backup so the backup package keeps working
        if (function_exists('lang_path') && is_dir(lang_path('backup'))) {
            app('translator')->addNamespace('backup', lang_path('backup'));
        }
        
        // Ensure Laravel 12 translation loader is properly configured
        $this->ensureTranslationLoaderCompatibility();
        
        // Register custom translation namespaces for multi-tenant support
        $this->registerTenantTranslationNamespaces();
    }
    
    /**
     * Ensure Laravel 12 translation loader compatibility
     */
    private function ensureTranslationLoaderCompatibility(): void
    {
        // Verify translation loader is properly bound (Laravel 12 should handle this automatically)
        if (! app()->bound('translation.loader')) {
            app()->singleton('translation.loader', function ($app) {
                return new \Illuminate\Translation\FileLoader($app['files'], $app['path.lang']);
            });
        }
    }
    
    /**
     * Register tenant-specific translation namespaces
     */
    private function registerTenantTranslationNamespaces(): void
    {
        // Register tenant-specific translation paths if they exist
        $tenantLangPath = base_path('lang/tenant');
        if (is_dir($tenantLangPath)) {
            app('translator')->addNamespace('tenant', $tenantLangPath);
        }
    }

    /**
     * Get all registered Filament aliases
     * 
     * @return array<string, string>
     */
    public function getFilamentAliases(): array
    {
        return self::FILAMENT_ALIASES;
    }
}