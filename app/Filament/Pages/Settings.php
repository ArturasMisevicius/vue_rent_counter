<?php

namespace App\Filament\Pages;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Filament\Actions\Admin\Settings\RenewOrganizationSubscriptionAction;
use App\Filament\Actions\Admin\Settings\UpdateNotificationPreferenceAction;
use App\Filament\Actions\Admin\Settings\UpdateOrganizationSettingsAction;
use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Http\Requests\Admin\Settings\RenewSubscriptionRequest;
use App\Http\Requests\Admin\Settings\UpdateNotificationPreferencesRequest;
use App\Http\Requests\Admin\Settings\UpdateOrganizationSettingsRequest;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationPreferenceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page
{
    use RefreshesOnShellLocaleUpdate;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    /**
     * @var array{
     *     organization_name: string,
     *     billing_contact_email: string,
     *     invoice_footer: string
     * }
     */
    public array $organizationForm = [];

    /**
     * @var array{
     *     new_invoice_generated: bool,
     *     invoice_overdue: bool,
     *     tenant_submits_reading: bool,
     *     subscription_expiring: bool
     * }
     */
    public array $notificationForm = [];

    /**
     * @var array{plan: string, duration: string}
     */
    public array $renewalForm = [];

    public ?string $currentPlan = null;

    public ?string $currentStatus = null;

    public ?string $currentExpiry = null;

    /**
     * @var array<int, array{
     *     key: string,
     *     label: string,
     *     used: int,
     *     limit: int,
     *     summary: string,
     *     percent: int,
     *     tone: string,
     *     limit_reached: bool,
     *     message: string
     * }>
     */
    public array $subscriptionUsage = [];

    public bool $showSubscriptionPanel = false;

    public function mount(): void
    {
        $this->ensureAdmin();

        app()->setLocale($this->user()->locale);
        $this->fillForms();
    }

    public function getTitle(): string
    {
        return __('shell.settings.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() ?? false;
    }

    public function saveSettings(
        UpdateOrganizationSettingsAction $updateOrganizationSettingsAction,
    ): void {
        $this->ensureAdmin();

        /** @var UpdateOrganizationSettingsRequest $request */
        $request = new UpdateOrganizationSettingsRequest;
        $attributes = $request->validatePayload($this->organizationForm, $this->user());

        $updateOrganizationSettingsAction->handle($this->organization(), $attributes);

        $this->fillForms();

        Notification::make()
            ->success()
            ->title(__('shell.settings.messages.organization_saved'))
            ->send();
    }

    public function saveOrganizationSettings(UpdateOrganizationSettingsAction $updateOrganizationSettingsAction): void
    {
        $this->saveSettings($updateOrganizationSettingsAction);
    }

    public function updatedNotificationForm(mixed $value, string $key): void
    {
        $this->ensureAdmin();

        /** @var UpdateNotificationPreferencesRequest $request */
        $request = new UpdateNotificationPreferencesRequest;
        $preferences = $request->validatePayload($this->notificationForm, $this->user());

        app(UpdateNotificationPreferenceAction::class)->handle($this->organization(), $preferences);

        $this->fillForms();
    }

    public function saveNotificationPreferences(): void
    {
        $this->updatedNotificationForm(true, NotificationPreferenceService::NEW_INVOICE_GENERATED);
    }

    public function renewSubscription(
        RenewOrganizationSubscriptionAction $renewOrganizationSubscriptionAction,
    ): void {
        $this->ensureAdmin();

        /** @var RenewSubscriptionRequest $request */
        $request = new RenewSubscriptionRequest;
        $attributes = $request->validatePayload($this->renewalForm, $this->user());

        $renewOrganizationSubscriptionAction->handle(
            $this->organization(),
            SubscriptionPlan::from($attributes['plan']),
            SubscriptionDuration::from($attributes['duration']),
        );

        $this->fillForms();
        $this->showSubscriptionPanel = false;

        Notification::make()
            ->success()
            ->title(__('shell.settings.messages.subscription_renewed'))
            ->send();
    }

    public function canManageOrganizationSettings(): bool
    {
        return $this->user()->isAdmin();
    }

    public function openSubscriptionPanel(): void
    {
        $this->ensureAdmin();
        $this->showSubscriptionPanel = true;
    }

    public function closeSubscriptionPanel(): void
    {
        $this->showSubscriptionPanel = false;
    }

    protected function fillForms(): void
    {
        $organization = $this->organization();
        $organization->loadMissing('settings');

        $settings = $organization->settings;
        $subscription = $this->currentSubscription($organization);
        $preferences = app(NotificationPreferenceService::class)->resolveForOrganization($organization);

        $this->organizationForm = [
            'organization_name' => (string) $organization->name,
            'billing_contact_email' => (string) ($settings?->billing_contact_email ?? ''),
            'invoice_footer' => (string) ($settings?->invoice_footer ?? ''),
        ];

        $this->notificationForm = [
            'new_invoice_generated' => (bool) ($preferences['new_invoice_generated'] ?? false),
            'invoice_overdue' => (bool) ($preferences['invoice_overdue'] ?? false),
            'tenant_submits_reading' => (bool) ($preferences['tenant_submits_reading'] ?? false),
            'subscription_expiring' => (bool) ($preferences['subscription_expiring'] ?? false),
        ];

        $this->renewalForm = [
            'plan' => $subscription?->plan?->value ?? SubscriptionPlan::BASIC->value,
            'duration' => SubscriptionDuration::MONTHLY->value,
        ];

        $this->currentPlan = $subscription?->plan?->value;
        $this->currentStatus = $subscription?->status?->value;
        $this->currentExpiry = $subscription?->expires_at?->toDateString();
        $this->subscriptionUsage = $this->resolveSubscriptionUsage($organization, $subscription);
    }

    protected function ensureAdmin(): void
    {
        abort_unless($this->user()->isAdmin(), 403);
    }

    /**
     * @return array<string, string>
     */
    public function getPlanOptions(): array
    {
        return SubscriptionPlan::options();
    }

    /**
     * @return array<string, string>
     */
    public function getDurationOptions(): array
    {
        return SubscriptionDuration::options();
    }

    protected function currentSubscription(Organization $organization): ?Subscription
    {
        $subscription = $organization->subscriptions()
            ->select([
                'id',
                'organization_id',
                'plan',
                'status',
                'starts_at',
                'expires_at',
                'is_trial',
                'property_limit_snapshot',
                'tenant_limit_snapshot',
                'meter_limit_snapshot',
                'invoice_limit_snapshot',
            ])
            ->latest('expires_at')
            ->latest('id')
            ->first();

        if ($subscription !== null) {
            $organization->loadCount([
                'properties',
                'users as tenants_count' => fn ($query) => $query->where('role', UserRole::TENANT),
            ]);

            $subscription->setRelation('organization', $organization);
        }

        return $subscription;
    }

    protected function organization(): Organization
    {
        /** @var Organization $organization */
        $organization = $this->user()->organization;

        return $organization;
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     used: int,
     *     limit: int,
     *     summary: string,
     *     percent: int,
     *     tone: string,
     *     limit_reached: bool,
     *     message: string
     * }>
     */
    private function resolveSubscriptionUsage(Organization $organization, ?Subscription $subscription): array
    {
        if ($subscription === null) {
            return [];
        }

        return [
            [
                'key' => 'properties',
                'label' => __('dashboard.organization_usage.properties'),
                'used' => $subscription->propertiesUsedCount(),
                'limit' => $subscription->propertyLimit(),
                'summary' => __('shell.settings.subscription.usage_summary', [
                    'used' => $subscription->propertiesUsedCount(),
                    'limit' => $subscription->propertyLimit(),
                    'label' => strtolower(__('dashboard.organization_usage.properties')),
                ]),
                'percent' => $subscription->propertyUsagePercent(),
                'tone' => $subscription->propertyUsageTone(),
                'limit_reached' => $subscription->hasReachedPropertyLimit(),
                'message' => __('shell.settings.subscription.limit_reached', [
                    'label' => strtolower(__('dashboard.organization_usage.properties')),
                ]),
            ],
            [
                'key' => 'tenants',
                'label' => __('dashboard.organization_usage.tenants'),
                'used' => $subscription->tenantsUsedCount(),
                'limit' => $subscription->tenantLimit(),
                'summary' => __('shell.settings.subscription.usage_summary', [
                    'used' => $subscription->tenantsUsedCount(),
                    'limit' => $subscription->tenantLimit(),
                    'label' => strtolower(__('dashboard.organization_usage.tenants')),
                ]),
                'percent' => $subscription->tenantUsagePercent(),
                'tone' => $subscription->tenantUsageTone(),
                'limit_reached' => $subscription->hasReachedTenantLimit(),
                'message' => __('shell.settings.subscription.limit_reached', [
                    'label' => strtolower(__('dashboard.organization_usage.tenants')),
                ]),
            ],
        ];
    }
}
