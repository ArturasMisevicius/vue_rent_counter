<?php

return [
    // Core Laravel Service Providers (order matters!)
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Auth\AuthServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,
    Illuminate\Bus\BusServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
    Illuminate\Cookie\CookieServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Mail\MailServiceProvider::class,
    Illuminate\Notifications\NotificationServiceProvider::class,
    Illuminate\Pagination\PaginationServiceProvider::class,
    Illuminate\Pipeline\PipelineServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Redis\RedisServiceProvider::class,
    Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class,
    Illuminate\Translation\TranslationServiceProvider::class,
    Illuminate\Validation\ValidationServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,

    // Livewire (required by Filament) - must come before Filament
    Livewire\LivewireServiceProvider::class,

    // Application Service Providers (before Filament)
    App\Providers\AppServiceProvider::class,
    App\Providers\DatabaseServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\ValidationServiceProvider::class,

    // Spatie Permission Service Provider
    Spatie\Permission\PermissionServiceProvider::class,

    // Filament Core Service Providers (after app providers)
    Filament\FilamentServiceProvider::class,
    Filament\Actions\ActionsServiceProvider::class,
    Filament\Forms\FormsServiceProvider::class,
    Filament\Infolists\InfolistsServiceProvider::class,
    Filament\Notifications\NotificationsServiceProvider::class,
    Filament\Schemas\SchemasServiceProvider::class,
    Filament\Support\SupportServiceProvider::class,
    Filament\Tables\TablesServiceProvider::class,
    Filament\Widgets\WidgetsServiceProvider::class,

    // Filament panel providers are intentionally disabled for custom web UI mode.
];
