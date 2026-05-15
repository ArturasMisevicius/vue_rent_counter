<?php

namespace App\Providers;

use App\Console\Commands\LaravelMissingTranslationsPhpFilesCommand;
use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Auth\ImpersonationManager;
use App\Filament\Support\Dashboard\DashboardCacheService;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Filament\Support\Shell\Search\GlobalSearchRegistry;
use App\Filament\Support\Shell\Search\Providers\BuildingSearchProvider;
use App\Filament\Support\Shell\Search\Providers\InvoiceSearchProvider;
use App\Filament\Support\Shell\Search\Providers\MeterReadingSearchProvider;
use App\Filament\Support\Shell\Search\Providers\OrganizationSearchProvider;
use App\Filament\Support\Shell\Search\Providers\PropertySearchProvider;
use App\Filament\Support\Shell\Search\Providers\TenantSearchProvider;
use App\Filament\Support\Superadmin\Integration\IntegrationProbeRegistry;
use App\Filament\Support\Superadmin\Integration\Probes\DatabaseProbe;
use App\Filament\Support\Superadmin\Integration\Probes\MailProbe;
use App\Filament\Support\Superadmin\Integration\Probes\QueueProbe;
use App\Http\Middleware\SetAuthenticatedUserLocale;
use App\Http\Middleware\SetGuestLocale;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Observers\OrganizationObserver;
use App\Observers\OrganizationUserObserver;
use App\Observers\ProjectObserver;
use App\Observers\PropertyAssignmentObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\SystemSettingObserver;
use App\Observers\UserObserver;
use App\Services\Billing\BillingService;
use App\Services\Localization\PhpFileMissingTranslationsScanner;
use App\Services\TranslationCacheService;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use MohamedSaid\LaravelMissingTranslations\Commands\LaravelMissingTranslationsCommand;
use MohamedSaid\LaravelMissingTranslations\LaravelMissingTranslations;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDisposableEmailFacadeCompatibilityAlias();

        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(BillingServiceInterface::class, BillingService::class);
        $this->app->singleton(LaravelMissingTranslations::class, PhpFileMissingTranslationsScanner::class);
        $this->app->singleton(LaravelMissingTranslationsCommand::class, LaravelMissingTranslationsPhpFilesCommand::class);
        $this->app->singleton(ImpersonationManager::class);
        $this->app->singleton(ManagerPermissionService::class);
        $this->app->scoped(DatabaseContentLocalizer::class);
        $this->app->scoped(DashboardCacheService::class);
        $this->app->singleton(TranslationCacheService::class, function (): TranslationCacheService {
            return new TranslationCacheService(
                cache()->store(),
                config('app.locale', 'en'),
                config('app.supported_locales', ['en' => 'EN', 'lt' => 'LT', 'ru' => 'RU']),
            );
        });
        $this->app->singleton(IntegrationProbeRegistry::class, function ($app): IntegrationProbeRegistry {
            return new IntegrationProbeRegistry([
                $app->make(DatabaseProbe::class),
                $app->make(QueueProbe::class),
                $app->make(MailProbe::class),
            ]);
        });

        $this->app->singleton(GlobalSearchRegistry::class, function ($app): GlobalSearchRegistry {
            return new GlobalSearchRegistry([
                $app->make(OrganizationSearchProvider::class),
                $app->make(BuildingSearchProvider::class),
                $app->make(PropertySearchProvider::class),
                $app->make(TenantSearchProvider::class),
                $app->make(InvoiceSearchProvider::class),
                $app->make(MeterReadingSearchProvider::class),
            ]);
        });
    }

    private function registerDisposableEmailFacadeCompatibilityAlias(): void
    {
        $expectedFacade = 'EragLaravelDisposableEmail\\Facades\\DisposableEmail';
        $actualFacade = 'EragLaravelDisposableEmail\\Facade\\DisposableEmail';

        if (! class_exists($expectedFacade) && class_exists($actualFacade)) {
            class_alias($actualFacade, $expectedFacade);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ManagerPermissionService::flushCache();

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Livewire::addPersistentMiddleware([
            SetGuestLocale::class,
            SetAuthenticatedUserLocale::class,
        ]);

        $this->warmFilamentTableTranslationGroups();
        $this->configureAuthRateLimiters();
        $this->configureSecurityRateLimiters();
        $this->configureFilamentDisplayFormats();
        $this->configureFilamentLocalizedLabels();
        $this->configureCalendarFields();
        $this->configureDestructiveActionConfirmations();

        Organization::observe(OrganizationObserver::class);
        OrganizationUser::observe(OrganizationUserObserver::class);
        Project::observe(ProjectObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        User::observe(UserObserver::class);
        SystemSetting::observe(SystemSettingObserver::class);
        PropertyAssignment::observe(PropertyAssignmentObserver::class);
    }

    private function warmFilamentTableTranslationGroups(): void
    {
        foreach (array_keys((array) config('app.supported_locales', ['en' => 'EN'])) as $locale) {
            if (! is_string($locale) || $locale === '') {
                continue;
            }

            Lang::get('filament-tables::table.fields.search.label', [], $locale);
        }
    }

    private function configureAuthRateLimiters(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'auth-login|'.$this->throttleKey($request),
            );
        });

        RateLimiter::for('password-reset-link', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'password-reset-link|'.$this->throttleKey($request),
            );
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $token = Str::lower((string) $request->input('token'));

            return Limit::perMinute(5)->by(
                'password-reset|'.$this->throttleKey($request).'|'.$token,
            );
        });

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                'auth-register|'.$this->throttleKey($request),
            );
        });
    }

    private function configureSecurityRateLimiters(): void
    {
        RateLimiter::for('security-csp-report', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                'security-csp-report|'.$request->ip(),
            );
        });
    }

    private function configureFilamentDisplayFormats(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultDateDisplayFormat(fn (): string => LocalizedDateFormatter::dateFormat())
                ->defaultDateTimeDisplayFormat(fn (): string => LocalizedDateFormatter::dateTimeFormat());
        });

        Schema::configureUsing(function (Schema $schema): void {
            $schema
                ->defaultDateDisplayFormat(fn (): string => LocalizedDateFormatter::dateFormat())
                ->defaultDateTimeDisplayFormat(fn (): string => LocalizedDateFormatter::dateTimeFormat());
        });
    }

    private function configureFilamentLocalizedLabels(): void
    {
        Resource::titleCaseModelLabel(false);
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();
    }

    private function configureCalendarFields(): void
    {
        if (! class_exists(DateTimePicker::class)) {
            return;
        }

        DateTimePicker::configureUsing(function (DateTimePicker $field): void {
            $field
                ->native(false)
                ->seconds(false)
                ->minutesStep(5)
                ->locale(fn (): string => app()->getLocale())
                ->firstDayOfWeek($this->calendarWeekStartsOn())
                ->displayFormat('Y-m-d H:i')
                ->suffixIcon(Heroicon::Calendar, isInline: true);
        });

        if (! class_exists(DatePicker::class)) {
            return;
        }

        DatePicker::configureUsing(function (DatePicker $field): void {
            $field
                ->native(false)
                ->closeOnDateSelection()
                ->locale(fn (): string => app()->getLocale())
                ->firstDayOfWeek($this->calendarWeekStartsOn())
                ->displayFormat('Y-m-d')
                ->suffixIcon(Heroicon::Calendar, isInline: true);
        });
    }

    private function calendarWeekStartsOn(): int
    {
        return in_array(app()->getLocale(), ['lt', 'ru', 'es'], true) ? 1 : 7;
    }

    private function configureDestructiveActionConfirmations(): void
    {
        if (! class_exists(DeleteAction::class)) {
            return;
        }

        DeleteAction::configureUsing(function (DeleteAction $action): void {
            $action
                ->requiresConfirmation()
                ->modalDescription(fn (mixed $record): string => __('shell.actions.destructive_confirm_single', [
                    'item' => $this->destructiveRecordLabel($record),
                ]));
        });

        if (class_exists(ForceDeleteAction::class)) {
            ForceDeleteAction::configureUsing(function (ForceDeleteAction $action): void {
                $action
                    ->requiresConfirmation()
                    ->modalDescription(fn (mixed $record): string => __('shell.actions.destructive_confirm_single', [
                        'item' => $this->destructiveRecordLabel($record),
                    ]));
            });
        }

        if (class_exists(DeleteBulkAction::class)) {
            DeleteBulkAction::configureUsing(function (DeleteBulkAction $action): void {
                $action
                    ->requiresConfirmation()
                    ->modalDescription(__('shell.actions.destructive_confirm_bulk'));
            });
        }
    }

    private function destructiveRecordLabel(mixed $record): string
    {
        if (! $record instanceof Model) {
            return __('shell.actions.destructive_item_fallback');
        }

        foreach (['name', 'title', 'full_name', 'invoice_number', 'identifier', 'slug', 'email'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        $key = $record->getKey();

        if (is_scalar($key) && (string) $key !== '') {
            return class_basename($record).' #'.(string) $key;
        }

        return __('shell.actions.destructive_item_fallback');
    }
}
