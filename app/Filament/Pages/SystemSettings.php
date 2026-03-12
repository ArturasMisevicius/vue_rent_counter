<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = null;

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperadmin(), 403);

        $this->form->fill($this->getDefaultData());
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('filament.pages.system_settings.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.pages.system_settings.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament.pages.system_settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Email Configuration Section
                Section::make(__('filament.pages.system_settings.sections.email.title'))
                    ->description(__('filament.pages.system_settings.sections.email.description'))
                    ->schema([
                        Select::make('mail_mailer')
                            ->label(__('filament.pages.system_settings.fields.mail_mailer'))
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => __('filament.pages.system_settings.options.mailers.sendmail'),
                                'mailgun' => __('filament.pages.system_settings.options.mailers.mailgun'),
                                'ses' => __('filament.pages.system_settings.options.mailers.ses'),
                                'log' => __('filament.pages.system_settings.options.mailers.log'),
                            ])
                            ->default('smtp')
                            ->required()
                            ->live(),

                        TextInput::make('mail_host')
                            ->label(__('filament.pages.system_settings.fields.mail_host'))
                            ->default('smtp.mailtrap.io')
                            ->required()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_port')
                            ->label(__('filament.pages.system_settings.fields.mail_port'))
                            ->numeric()
                            ->default(2525)
                            ->required()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_username')
                            ->label(__('filament.pages.system_settings.fields.mail_username'))
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_password')
                            ->label(__('filament.pages.system_settings.fields.mail_password'))
                            ->password()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Select::make('mail_encryption')
                            ->label(__('filament.pages.system_settings.fields.mail_encryption'))
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => __('filament.pages.system_settings.options.none'),
                            ])
                            ->default('tls')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_from_address')
                            ->label(__('filament.pages.system_settings.fields.mail_from_address'))
                            ->email()
                            ->default('noreply@example.com')
                            ->required(),

                        TextInput::make('mail_from_name')
                            ->label(__('filament.pages.system_settings.fields.mail_from_name'))
                            ->default(__('app.brand.name'))
                            ->required(),
                    ])
                    ->columns(2),

                // Backup Configuration Section
                Section::make(__('filament.pages.system_settings.sections.backup.title'))
                    ->description(__('filament.pages.system_settings.sections.backup.description'))
                    ->schema([
                        TextInput::make('backup_schedule')
                            ->label(__('filament.pages.system_settings.fields.backup_schedule'))
                            ->default('0 2 * * *')
                            ->helperText(__('filament.pages.system_settings.helpers.backup_schedule'))
                            ->required(),

                        TextInput::make('backup_retention_days')
                            ->label(__('filament.pages.system_settings.fields.backup_retention_days'))
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->required()
                            ->helperText(__('filament.pages.system_settings.helpers.backup_retention_days')),

                        TextInput::make('backup_storage_location')
                            ->label(__('filament.pages.system_settings.fields.backup_storage_location'))
                            ->default('local')
                            ->helperText(__('filament.pages.system_settings.helpers.backup_storage_location'))
                            ->required(),

                        Toggle::make('backup_notifications_enabled')
                            ->label(__('filament.pages.system_settings.fields.backup_notifications_enabled'))
                            ->default(true)
                            ->helperText(__('filament.pages.system_settings.helpers.backup_notifications_enabled')),
                    ])
                    ->columns(2),

                // Queue Configuration Section
                Section::make(__('filament.pages.system_settings.sections.queue.title'))
                    ->description(__('filament.pages.system_settings.sections.queue.description'))
                    ->schema([
                        Select::make('queue_default_connection')
                            ->label(__('filament.pages.system_settings.fields.queue_default_connection'))
                            ->options([
                                'sync' => __('filament.pages.system_settings.options.queue_connections.sync'),
                                'database' => __('filament.pages.system_settings.options.queue_connections.database'),
                                'redis' => 'Redis',
                                'sqs' => __('filament.pages.system_settings.options.queue_connections.sqs'),
                            ])
                            ->default('database')
                            ->required(),

                        TextInput::make('queue_priorities')
                            ->label(__('filament.pages.system_settings.fields.queue_priorities'))
                            ->default('high,default,low')
                            ->helperText(__('filament.pages.system_settings.helpers.queue_priorities'))
                            ->required(),

                        TextInput::make('queue_retry_attempts')
                            ->label(__('filament.pages.system_settings.fields.queue_retry_attempts'))
                            ->numeric()
                            ->default(3)
                            ->minValue(0)
                            ->maxValue(10)
                            ->required()
                            ->helperText(__('filament.pages.system_settings.helpers.queue_retry_attempts')),

                        TextInput::make('queue_timeout')
                            ->label(__('filament.pages.system_settings.fields.queue_timeout'))
                            ->numeric()
                            ->default(60)
                            ->minValue(10)
                            ->maxValue(3600)
                            ->required()
                            ->helperText(__('filament.pages.system_settings.helpers.queue_timeout')),
                    ])
                    ->columns(2),

                // Feature Flags Section
                Section::make(__('filament.pages.system_settings.sections.features.title'))
                    ->description(__('filament.pages.system_settings.sections.features.description'))
                    ->schema([
                        Toggle::make('feature_maintenance_mode')
                            ->label(__('filament.pages.system_settings.fields.feature_maintenance_mode'))
                            ->default(false)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_maintenance_mode')),

                        Toggle::make('feature_user_registration')
                            ->label(__('filament.pages.system_settings.fields.feature_user_registration'))
                            ->default(true)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_user_registration')),

                        Toggle::make('feature_api_access')
                            ->label(__('filament.pages.system_settings.fields.feature_api_access'))
                            ->default(true)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_api_access')),

                        Toggle::make('feature_debug_mode')
                            ->label(__('filament.pages.system_settings.fields.feature_debug_mode'))
                            ->default(false)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_debug_mode')),

                        Toggle::make('feature_beta_features')
                            ->label(__('filament.pages.system_settings.fields.feature_beta_features'))
                            ->default(false)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_beta_features')),

                        Toggle::make('feature_analytics')
                            ->label(__('filament.pages.system_settings.fields.feature_analytics'))
                            ->default(true)
                            ->helperText(__('filament.pages.system_settings.helpers.feature_analytics')),
                    ])
                    ->columns(2),

                // Platform Settings Section
                Section::make(__('filament.pages.system_settings.sections.platform.title'))
                    ->description(__('filament.pages.system_settings.sections.platform.description'))
                    ->schema([
                        Select::make('platform_default_timezone')
                            ->label(__('filament.pages.system_settings.fields.platform_default_timezone'))
                            ->options([
                                'Europe/Vilnius' => 'Europe/Vilnius',
                                'UTC' => 'UTC',
                                'Europe/London' => 'Europe/London',
                                'America/New_York' => 'America/New_York',
                                'Asia/Tokyo' => 'Asia/Tokyo',
                            ])
                            ->default('Europe/Vilnius')
                            ->searchable()
                            ->required(),

                        Select::make('platform_default_locale')
                            ->label(__('filament.pages.system_settings.fields.platform_default_locale'))
                            ->options([
                                'lt' => __('filament.pages.system_settings.options.locales.lt'),
                                'en' => __('filament.pages.system_settings.options.locales.en'),
                                'ru' => __('filament.pages.system_settings.options.locales.ru'),
                            ])
                            ->default('lt')
                            ->required(),

                        Select::make('platform_default_currency')
                            ->label(__('filament.pages.system_settings.fields.platform_default_currency'))
                            ->options([
                                'EUR' => __('filament.pages.system_settings.options.currencies.eur'),
                                'USD' => __('filament.pages.system_settings.options.currencies.usd'),
                                'GBP' => __('filament.pages.system_settings.options.currencies.gbp'),
                            ])
                            ->default('EUR')
                            ->required(),

                        TextInput::make('platform_session_timeout')
                            ->label(__('filament.pages.system_settings.fields.platform_session_timeout'))
                            ->numeric()
                            ->default(120)
                            ->minValue(5)
                            ->maxValue(1440)
                            ->required()
                            ->helperText(__('filament.pages.system_settings.helpers.platform_session_timeout')),

                        TextInput::make('platform_password_min_length')
                            ->label(__('filament.pages.system_settings.fields.platform_password_min_length'))
                            ->numeric()
                            ->default(8)
                            ->minValue(6)
                            ->maxValue(32)
                            ->required(),

                        Toggle::make('platform_password_require_uppercase')
                            ->label(__('filament.pages.system_settings.fields.platform_password_require_uppercase'))
                            ->default(true),

                        Toggle::make('platform_password_require_numbers')
                            ->label(__('filament.pages.system_settings.fields.platform_password_require_numbers'))
                            ->default(true),

                        Toggle::make('platform_password_require_symbols')
                            ->label(__('filament.pages.system_settings.fields.platform_password_require_symbols'))
                            ->default(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testEmail')
                ->label(__('filament.pages.system_settings.actions.test_email'))
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('filament.pages.system_settings.modals.test_email.heading'))
                ->modalDescription(__('filament.pages.system_settings.modals.test_email.description'))
                ->action(function () {
                    try {
                        Mail::raw(__('filament.pages.system_settings.mail.test_email_body'), function ($message) {
                            $message->to(auth()->user()->email)
                                ->subject(__('filament.pages.system_settings.mail.test_email_subject'));
                        });

                        Notification::make()
                            ->title(__('filament.pages.system_settings.notifications.test_email_sent_title'))
                            ->body(__('filament.pages.system_settings.notifications.test_email_sent_body', [
                                'email' => auth()->user()->email,
                            ]))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('filament.pages.system_settings.notifications.test_email_failed_title'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('save')
                ->label(__('filament.pages.system_settings.actions.save'))
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('saveConfiguration'),

            Action::make('reset')
                ->label(__('filament.pages.system_settings.actions.reset'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action('resetToDefaults'),

            Action::make('export')
                ->label(__('filament.pages.system_settings.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('exportConfiguration'),

            Action::make('import')
                ->label(__('filament.pages.system_settings.actions.import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->requiresConfirmation()
                ->action('importConfiguration'),
        ];
    }

    public function saveConfiguration(): void
    {
        $data = $this->form->getState();

        try {
            // Save to config file or database
            $this->saveToEnvFile($data);

            // Clear config cache
            Artisan::call('config:clear');

            Notification::make()
                ->title(__('filament.pages.system_settings.notifications.configuration_saved_title'))
                ->body(__('filament.pages.system_settings.notifications.configuration_saved_body'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament.pages.system_settings.notifications.configuration_save_failed_title'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetToDefaults(): void
    {
        $this->form->fill($this->getDefaultData());

        Notification::make()
            ->title(__('filament.pages.system_settings.notifications.configuration_reset_title'))
            ->body(__('filament.pages.system_settings.notifications.configuration_reset_body'))
            ->success()
            ->send();
    }

    public function exportConfiguration(): mixed
    {
        $data = $this->form->getState();
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'system-settings-'.now()->format('Y-m-d-His').'.json');
    }

    public function importConfiguration(): void
    {
        // This would handle file upload and import
        Notification::make()
            ->title(__('filament.pages.system_settings.notifications.import_not_implemented_title'))
            ->body(__('filament.pages.system_settings.notifications.import_not_implemented_body'))
            ->warning()
            ->send();
    }

    protected function getDefaultData(): array
    {
        return [
            // Email Configuration
            'mail_mailer' => config('mail.default', 'smtp'),
            'mail_host' => config('mail.mailers.smtp.host', 'smtp.mailtrap.io'),
            'mail_port' => config('mail.mailers.smtp.port', 2525),
            'mail_username' => config('mail.mailers.smtp.username', ''),
            'mail_password' => config('mail.mailers.smtp.password', ''),
            'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            'mail_from_address' => config('mail.from.address', 'noreply@example.com'),
            'mail_from_name' => config('mail.from.name', __('app.brand.name')),

            // Backup Configuration
            'backup_schedule' => '0 2 * * *',
            'backup_retention_days' => 30,
            'backup_storage_location' => 'local',
            'backup_notifications_enabled' => true,

            // Queue Configuration
            'queue_default_connection' => config('queue.default', 'database'),
            'queue_priorities' => 'high,default,low',
            'queue_retry_attempts' => 3,
            'queue_timeout' => 60,

            // Feature Flags
            'feature_maintenance_mode' => app()->isDownForMaintenance(),
            'feature_user_registration' => true,
            'feature_api_access' => true,
            'feature_debug_mode' => config('app.debug', false),
            'feature_beta_features' => false,
            'feature_analytics' => true,

            // Platform Settings
            'platform_default_timezone' => config('app.timezone', 'Europe/Vilnius'),
            'platform_default_locale' => config('app.locale', 'lt'),
            'platform_default_currency' => 'EUR',
            'platform_session_timeout' => config('session.lifetime', 120),
            'platform_password_min_length' => 8,
            'platform_password_require_uppercase' => true,
            'platform_password_require_numbers' => true,
            'platform_password_require_symbols' => false,
        ];
    }

    protected function saveToEnvFile(array $data): void
    {
        // This is a simplified version - in production, you'd want to use
        // a more robust method to update .env file or store in database
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            throw new \Exception(__('filament.pages.system_settings.errors.env_file_not_found'));
        }

        $envContent = File::get($envPath);

        // Update mail settings
        $envContent = $this->updateEnvValue($envContent, 'MAIL_MAILER', $data['mail_mailer']);
        $envContent = $this->updateEnvValue($envContent, 'MAIL_HOST', $data['mail_host']);
        $envContent = $this->updateEnvValue($envContent, 'MAIL_PORT', $data['mail_port']);
        $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_ADDRESS', $data['mail_from_address']);
        $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_NAME', $data['mail_from_name']);

        // Update queue settings
        $envContent = $this->updateEnvValue($envContent, 'QUEUE_CONNECTION', $data['queue_default_connection']);

        // Update app settings
        $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $data['platform_default_timezone']);
        $envContent = $this->updateEnvValue($envContent, 'APP_LOCALE', $data['platform_default_locale']);
        $envContent = $this->updateEnvValue($envContent, 'SESSION_LIFETIME', $data['platform_session_timeout']);

        File::put($envPath, $envContent);
    }

    protected function updateEnvValue(string $envContent, string $key, mixed $value): string
    {
        $oldValue = env($key);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, "{$key}={$value}", $envContent);
        }

        return $envContent."\n{$key}={$value}";
    }
}
