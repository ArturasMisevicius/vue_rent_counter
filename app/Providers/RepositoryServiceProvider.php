<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\InvoiceRepositoryInterface;
use App\Contracts\PropertyRepositoryInterface;
use App\Contracts\RepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use App\Repositories\BaseRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\PropertyRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * 
 * Registers repository interfaces with their concrete implementations.
 * This provider handles dependency injection bindings for the repository pattern.
 * 
 * Binding Strategy:
 * - Interfaces are bound to concrete implementations
 * - Repositories are registered as singletons for performance
 * - Model instances are injected into repositories
 * 
 * Usage:
 * - Add this provider to config/app.php providers array
 * - Inject repository interfaces in controllers/services
 * - Laravel will automatically resolve the correct implementation
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings.
     * 
     * Maps interface contracts to their concrete implementations.
     * 
     * @var array<class-string, class-string>
     */
    protected array $repositories = [
        UserRepositoryInterface::class => UserRepository::class,
        PropertyRepositoryInterface::class => PropertyRepository::class,
        InvoiceRepositoryInterface::class => InvoiceRepository::class,
    ];

    /**
     * Register repository services.
     * 
     * This method is called during the service container registration phase.
     * All repository bindings are registered here.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->registerRepositories();
        $this->registerBaseRepository();
    }

    /**
     * Bootstrap repository services.
     * 
     * This method is called after all service providers have been registered.
     * Any bootstrapping logic can be added here.
     * 
     * @return void
     */
    public function boot(): void
    {
        // Repository bootstrapping logic can be added here
        // For example: event listeners, middleware, etc.
    }

    /**
     * Register all repository bindings.
     * 
     * Each repository is bound as a singleton to improve performance
     * and ensure consistent state throughout the request lifecycle.
     * 
     * @return void
     */
    protected function registerRepositories(): void
    {
        // User Repository
        $this->app->singleton(UserRepositoryInterface::class, function ($app) {
            return new UserRepository($app->make(User::class));
        });

        // Property Repository
        $this->app->singleton(PropertyRepositoryInterface::class, function ($app) {
            return new PropertyRepository($app->make(Property::class));
        });

        // Invoice Repository
        $this->app->singleton(InvoiceRepositoryInterface::class, function ($app) {
            return new InvoiceRepository($app->make(Invoice::class));
        });

        // Register additional repositories here as needed
        // Example:
        // $this->app->singleton(MeterRepositoryInterface::class, function ($app) {
        //     return new MeterRepository($app->make(Meter::class));
        // });
    }

    /**
     * Register the base repository for generic usage.
     * 
     * This allows injection of the base repository when specific
     * repository interfaces are not needed.
     * 
     * @return void
     */
    protected function registerBaseRepository(): void
    {
        $this->app->bind(RepositoryInterface::class, function ($app, $parameters) {
            // Extract model from parameters or use a default
            $model = $parameters['model'] ?? $app->make(User::class);
            
            return new class($model) extends BaseRepository {
                // Anonymous class extending BaseRepository for generic usage
            };
        });
    }

    /**
     * Get the services provided by the provider.
     * 
     * @return array<int, string>
     */
    public function provides(): array
    {
        return array_keys($this->repositories);
    }

    /**
     * Register a new repository binding.
     * 
     * This method allows dynamic registration of repository bindings
     * at runtime, useful for packages or dynamic module loading.
     * 
     * @param string $interface The repository interface
     * @param string $implementation The concrete implementation
     * @param bool $singleton Whether to bind as singleton
     * @return void
     */
    public function registerRepository(string $interface, string $implementation, bool $singleton = true): void
    {
        if ($singleton) {
            $this->app->singleton($interface, $implementation);
        } else {
            $this->app->bind($interface, $implementation);
        }

        $this->repositories[$interface] = $implementation;
    }

    /**
     * Check if a repository is registered.
     * 
     * @param string $interface The repository interface
     * @return bool
     */
    public function hasRepository(string $interface): bool
    {
        return isset($this->repositories[$interface]);
    }

    /**
     * Get all registered repositories.
     * 
     * @return array<class-string, class-string>
     */
    public function getRepositories(): array
    {
        return $this->repositories;
    }
}