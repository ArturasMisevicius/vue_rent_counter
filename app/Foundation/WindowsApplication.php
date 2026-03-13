<?php

declare(strict_types=1);

namespace App\Foundation;

use Illuminate\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

/**
 * Windows-compatible Laravel Application that uses WindowsProviderRepository
 * to bypass the is_writable() bug on Windows systems.
 */
class WindowsApplication extends Application
{
    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        // Ensure filesystem service is available
        if (!$this->bound('files')) {
            $this->singleton('files', function () {
                return new Filesystem;
            });
        }

        // Laravel 12 uses bootstrap/providers.php instead of config/app.php
        $providersPath = $this->basePath('bootstrap/providers.php');
        
        if (file_exists($providersPath)) {
            $providers = require $providersPath;
        } else {
            // Fallback to default Laravel providers if bootstrap/providers.php doesn't exist
            $providers = $this->getDefaultProviders();
        }

        // Use our Windows-compatible ProviderRepository
        (new WindowsProviderRepository($this, $this->make('files'), $this->getCachedServicesPath()))
            ->load($providers);
    }

    /**
     * Get default Laravel providers as fallback.
     */
    protected function getDefaultProviders(): array
    {
        return [
            \Illuminate\Foundation\Providers\FoundationServiceProvider::class,
            \Illuminate\Auth\AuthServiceProvider::class,
            \Illuminate\Broadcasting\BroadcastServiceProvider::class,
            \Illuminate\Bus\BusServiceProvider::class,
            \Illuminate\Cache\CacheServiceProvider::class,
            \Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
            \Illuminate\Cookie\CookieServiceProvider::class,
            \Illuminate\Database\DatabaseServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Filesystem\FilesystemServiceProvider::class,
            \Illuminate\Hashing\HashServiceProvider::class,
            \Illuminate\Mail\MailServiceProvider::class,
            \Illuminate\Notifications\NotificationServiceProvider::class,
            \Illuminate\Pagination\PaginationServiceProvider::class,
            \Illuminate\Pipeline\PipelineServiceProvider::class,
            \Illuminate\Queue\QueueServiceProvider::class,
            \Illuminate\Redis\RedisServiceProvider::class,
            \Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
            \Illuminate\Session\SessionServiceProvider::class,
            \Illuminate\Translation\TranslationServiceProvider::class,
            \Illuminate\Validation\ValidationServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
        ];
    }
}