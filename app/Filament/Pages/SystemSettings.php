<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'System Settings';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Email Configuration Section
                Section::make('Email Configuration')
                    ->description('Configure SMTP settings and email notifications')
                    ->schema([
                        Select::make('mail_mailer')
                            ->label('Mail Driver')
                            ->options([
                                'smtp' => 'SMTP',
                                'sendmail' => 'Sendmail',
                                'mailgun' => 'Mailgun',
                                'ses' => 'Amazon SES',
                                'log' => 'Log (Development)',
                            ])
                            ->default('smtp')
                            ->required()
                            ->live(),

                        TextInput::make('mail_host')
                            ->label('SMTP Host')
                            ->default('smtp.mailtrap.io')
                            ->required()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_port')
                            ->label('SMTP Port')
                            ->numeric()
                            ->default(2525)
                            ->required()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_username')
                            ->label('SMTP Username')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_password')
                            ->label('SMTP Password')
                            ->password()
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                '' => 'None',
                            ])
                            ->default('tls')
                            ->visible(fn ($get) => $get('mail_mailer') === 'smtp'),

                        TextInput::make('mail_from_address')
                            ->label('From Email Address')
                            ->email()
                            ->default('noreply@example.com')
                            ->required(),

                        TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->default('Vilnius Utilities')
                            ->required(),
                    ])
                    ->columns(2),

                // Backup Configuration Section
                Section::make('Backup Configuration')
                    ->description('Configure automated backup settings')
                    ->schema([
                        TextInput::make('backup_schedule')
                            ->label('Backup Schedule (Cron Expression)')
                            ->default('0 2 * * *')
                            ->helperText('Default: Daily at 2:00 AM (0 2 * * *)')
                            ->required(),

                        TextInput::make('backup_retention_days')
                            ->label('Retention Period (Days)')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->required()
                            ->helperText('Number of days to keep backups'),

                        TextInput::make('backup_storage_location')
                            ->label('Storage Location')
                            ->default('local')
                            ->helperText('Storage disk name (local, s3, etc.)')
                            ->required(),

                        Toggle::make('backup_notifications_enabled')
                            ->label('Enable Backup Notifications')
                            ->default(true)
                            ->helperText('Send email notifications on backup success/failure'),
                    ])
                    ->columns(2),

                // Queue Configuration Section
                Section::make('Queue Configuration')
                    ->description('Configure queue and job processing settings')
                    ->schema([
                        Select::make('queue_default_connection')
                            ->label('Default Queue Connection')
                            ->options([
                                'sync' => 'Sync (No Queue)',
                                'database' => 'Database',
                                'redis' => 'Redis',
                                'sqs' => 'Amazon SQS',
                            ])
                            ->default('database')
                            ->required(),

                        TextInput::make('queue_priorities')
                            ->label('Queue Priorities')
                            ->default('high,default,low')
                            ->helperText('Comma-separated list of queue names in priority order')
                            ->required(),

                        TextInput::make('queue_retry_attempts')
                            ->label('Retry Attempts')
                            ->numeric()
                            ->default(3)
                            ->minValue(0)
                            ->maxValue(10)
                            ->required()
                            ->helperText('Number of times to retry failed jobs'),

                        TextInput::make('queue_timeout')
                            ->label('Job Timeout (Seconds)')
                            ->numeric()
                            ->default(60)
                            ->minValue(10)
                            ->maxValue(3600)
                            ->required()
                            ->helperText('Maximum execution time for jobs'),
                    ])
                    ->columns(2),

                // Feature Flags Section
                Section::make('Feature Flags')
                    ->description('Enable or disable platform features')
                    ->schema([
                        Toggle::make('feature_maintenance_mode')
                            ->label('Maintenance Mode')
                            ->default(false)
                            ->helperText('Put the entire platform in maintenance mode'),

                        Toggle::make('feature_user_registration')
                            ->label('User Registration')
                            ->default(true)
                            ->helperText('Allow new user registrations'),

                        Toggle::make('feature_api_access')
                            ->label('API Access')
                            ->default(true)
                            ->helperText('Enable API endpoints'),

                        Toggle::make('feature_debug_mode')
                            ->label('Debug Mode')
                            ->default(false)
                            ->helperText('Enable detailed error messages (development only)'),

                        Toggle::make('feature_beta_features')
                            ->label('Beta Features')
                            ->default(false)
                            ->helperText('Enable experimental features for all organizations'),

                        Toggle::make('feature_analytics')
                            ->label('Analytics Tracking')
                            ->default(true)
                            ->helperText('Enable platform analytics and usage tracking'),
                    ])
                    ->columns(2),

                // Platform Settings Section
                Section::make('Platform Settings')
                    ->description('Configure default platform-wide settings')
                    ->schema([
                        Select::make('platform_default_timezone')
                            ->label('Default Timezone')
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
                            ->label('Default Locale')
                            ->options([
                                'lt' => 'Lithuanian',
                                'en' => 'English',
                                'ru' => 'Russian',
                            ])
                            ->default('lt')
                            ->required(),

                        Select::make('platform_default_currency')
                            ->label('Default Currency')
                            ->options([
                                'EUR' => 'Euro (EUR)',
                                'USD' => 'US Dollar (USD)',
                                'GBP' => 'British Pound (GBP)',
                            ])
                            ->default('EUR')
                            ->required(),

                        TextInput::make('platform_session_timeout')
                            ->label('Session Timeout (Minutes)')
                            ->numeric()
                            ->default(120)
                            ->minValue(5)
                            ->maxValue(1440)
                            ->required()
                            ->helperText('User session lifetime in minutes'),

                        TextInput::make('platform_password_min_length')
                            ->label('Minimum Password Length')
                            ->numeric()
                            ->default(8)
                            ->minValue(6)
                            ->maxValue(32)
                            ->required(),

                        Toggle::make('platform_password_require_uppercase')
                            ->label('Require Uppercase Letters')
                            ->default(true),

                        Toggle::make('platform_password_require_numbers')
                            ->label('Require Numbers')
                            ->default(true),

                        Toggle::make('platform_password_require_symbols')
                            ->label('Require Special Characters')
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
                ->label('Send Test Email')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Send Test Email')
                ->modalDescription('This will send a test email to verify your SMTP configuration.')
                ->action(function () {
                    try {
                        Mail::raw('This is a test email from Vilnius Utilities Platform.', function ($message) {
                            $message->to(auth()->user()->email)
                                ->subject('Test Email - System Settings');
                        });

                        Notification::make()
                            ->title('Test email sent')
                            ->body('Check your inbox at ' . auth()->user()->email)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send test email')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('save')
                ->label('Save Configuration')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('saveConfiguration'),

            Action::make('reset')
                ->label('Reset to Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action('resetToDefaults'),

            Action::make('export')
                ->label('Export Configuration')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action('exportConfiguration'),

            Action::make('import')
                ->label('Import Configuration')
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
                ->title('Configuration saved')
                ->body('System settings have been updated successfully.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to save configuration')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetToDefaults(): void
    {
        $this->form->fill($this->getDefaultData());
        
        Notification::make()
            ->title('Configuration reset')
            ->body('All settings have been reset to default values.')
            ->success()
            ->send();
    }

    public function exportConfiguration(): mixed
    {
        $data = $this->form->getState();
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, 'system-settings-' . now()->format('Y-m-d-His') . '.json');
    }

    public function importConfiguration(): void
    {
        // This would handle file upload and import
        Notification::make()
            ->title('Import not yet implemented')
            ->body('Configuration import functionality will be available soon.')
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
            'mail_from_name' => config('mail.from.name', 'Vilnius Utilities'),

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
        
        if (!File::exists($envPath)) {
            throw new \Exception('.env file not found');
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
        
        return $envContent . "\n{$key}={$value}";
    }
}
