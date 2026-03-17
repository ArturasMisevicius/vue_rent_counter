<?php

namespace App\Filament\Pages;

use App\Actions\Admin\Settings\RenewOrganizationSubscriptionAction;
use App\Actions\Admin\Settings\UpdateNotificationPreferenceAction;
use App\Actions\Admin\Settings\UpdateOrganizationSettingsAction;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Filament\Pages\Concerns\InteractsWithAccountProfileForms;
use App\Models\Organization;
use App\Models\Subscription;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Settings extends Page
{
    use InteractsWithAccountProfileForms;

    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    /**
     * @var array{
     *     billing_contact_name: string,
     *     billing_contact_email: string,
     *     billing_contact_phone: string,
     *     payment_instructions: string,
     *     invoice_footer: string
     * }
     */
    public array $organizationForm = [];

    /**
     * @var array{invoice_reminders: bool, reading_deadline_alerts: bool}
     */
    public array $notificationForm = [];

    /**
     * @var array{plan: string, duration: string}
     */
    public array $renewalForm = [];

    public ?string $currentPlan = null;

    public ?string $currentStatus = null;

    public ?string $currentExpiry = null;

    public function mount(): void
    {
        app()->setLocale($this->user()->locale);
        $this->fillAccountProfileForms();

        if ($this->canManageOrganizationSettings()) {
            $this->fillForms();
        }
    }

    public function getTitle(): string
    {
        return __('shell.settings.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }

    public function saveOrganizationSettings(
        UpdateOrganizationSettingsAction $updateOrganizationSettingsAction,
    ): void {
        $this->ensureAdmin();

        $attributes = Validator::make($this->organizationForm, [
            'billing_contact_name' => ['nullable', 'string', 'max:255'],
            'billing_contact_email' => ['nullable', 'email', 'max:255'],
            'billing_contact_phone' => ['nullable', 'string', 'max:255'],
            'payment_instructions' => ['nullable', 'string'],
            'invoice_footer' => ['nullable', 'string'],
        ])->validate();

        $updateOrganizationSettingsAction->handle($this->organization(), $attributes);

        $this->fillForms();

        Notification::make()
            ->success()
            ->title(__('shell.settings.messages.organization_saved'))
            ->send();
    }

    public function saveNotificationPreferences(
        UpdateNotificationPreferenceAction $updateNotificationPreferenceAction,
    ): void {
        $this->ensureAdmin();

        $preferences = Validator::make($this->notificationForm, [
            'invoice_reminders' => ['required', 'boolean'],
            'reading_deadline_alerts' => ['required', 'boolean'],
        ])->validate();

        $updateNotificationPreferenceAction->handle($this->organization(), $preferences);

        $this->fillForms();

        Notification::make()
            ->success()
            ->title(__('shell.settings.messages.notifications_saved'))
            ->send();
    }

    public function renewSubscription(
        RenewOrganizationSubscriptionAction $renewOrganizationSubscriptionAction,
    ): void {
        $this->ensureAdmin();

        $attributes = Validator::make($this->renewalForm, [
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'duration' => ['required', Rule::enum(SubscriptionDuration::class)],
        ])->validate();

        $renewOrganizationSubscriptionAction->handle(
            $this->organization(),
            SubscriptionPlan::from($attributes['plan']),
            SubscriptionDuration::from($attributes['duration']),
        );

        $this->fillForms();

        Notification::make()
            ->success()
            ->title(__('shell.settings.messages.subscription_renewed'))
            ->send();
    }

    public function canManageOrganizationSettings(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function fillForms(): void
    {
        $organization = $this->organization();
        $organization->loadMissing('settings');

        $settings = $organization->settings;
        $subscription = $this->currentSubscription($organization);
        $preferences = $settings?->notification_preferences ?? [];

        $this->organizationForm = [
            'billing_contact_name' => (string) ($settings?->billing_contact_name ?? ''),
            'billing_contact_email' => (string) ($settings?->billing_contact_email ?? ''),
            'billing_contact_phone' => (string) ($settings?->billing_contact_phone ?? ''),
            'payment_instructions' => (string) ($settings?->payment_instructions ?? ''),
            'invoice_footer' => (string) ($settings?->invoice_footer ?? ''),
        ];

        $this->notificationForm = [
            'invoice_reminders' => (bool) ($preferences['invoice_reminders'] ?? false),
            'reading_deadline_alerts' => (bool) ($preferences['reading_deadline_alerts'] ?? false),
        ];

        $this->renewalForm = [
            'plan' => $subscription?->plan?->value ?? SubscriptionPlan::BASIC->value,
            'duration' => SubscriptionDuration::MONTHLY->value,
        ];

        $this->currentPlan = $subscription?->plan?->value;
        $this->currentStatus = $subscription?->status?->value;
        $this->currentExpiry = $subscription?->expires_at?->toDateString();
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
        return collect(SubscriptionPlan::cases())
            ->mapWithKeys(fn (SubscriptionPlan $plan): array => [
                $plan->value => __("shell.settings.subscription.plans.{$plan->value}"),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function getDurationOptions(): array
    {
        return collect(SubscriptionDuration::cases())
            ->mapWithKeys(fn (SubscriptionDuration $duration): array => [
                $duration->value => __("shell.settings.subscription.durations.{$duration->value}"),
            ])
            ->all();
    }

    protected function currentSubscription(Organization $organization): ?Subscription
    {
        return $organization->subscriptions()
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
    }

    protected function organization(): Organization
    {
        /** @var Organization $organization */
        $organization = $this->user()->organization;

        return $organization;
    }
}
