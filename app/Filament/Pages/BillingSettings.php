<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\BillingFrequency;
use App\Filament\Actions\Admin\Settings\UpdateOrganizationBillingSettingsAction;
use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Http\Requests\Admin\Settings\UpdateOrganizationBillingSettingsRequest;
use App\Models\Organization;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BillingSettings extends Page
{
    use RefreshesOnShellLocaleUpdate;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'settings/billing';

    protected string $view = 'filament.pages.billing-settings';

    /**
     * @var array{
     *     auto_generation_enabled: bool,
     *     billing_frequency: string,
     *     invoice_generation_day: int,
     *     reading_deadline_day: int,
     *     payment_due_days: int,
     *     send_created_notification: bool,
     *     send_reminders: bool,
     *     reminder_days_before_deadline: list<int>,
     *     timezone: string,
     *     default_currency: string
     * }
     */
    public array $billingForm = [];

    public string $reminderDays = '';

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        app()->setLocale($this->user()->locale);
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return __('shell.settings.billing.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }

    public function saveBillingSettings(UpdateOrganizationBillingSettingsAction $action): void
    {
        $request = new UpdateOrganizationBillingSettingsRequest;
        $attributes = $request->validatePayload([
            ...$this->billingForm,
            'reminder_days_before_deadline' => $this->normalizedReminderDays(),
        ], $this->user());

        $action->handle($this->organization(), $attributes);

        $this->fillForm();

        Notification::make()
            ->success()
            ->title(__('shell.settings.billing.messages.saved'))
            ->send();
    }

    /**
     * @return array<string, string>
     */
    public function getFrequencyOptions(): array
    {
        return BillingFrequency::options();
    }

    /**
     * @return array<int, string>
     */
    public function getTimezoneOptions(): array
    {
        return [
            'UTC' => 'UTC',
            'Europe/Vilnius' => 'Europe/Vilnius',
            'Europe/Riga' => 'Europe/Riga',
            'Europe/Tallinn' => 'Europe/Tallinn',
            'Europe/Warsaw' => 'Europe/Warsaw',
        ];
    }

    private function fillForm(): void
    {
        $organization = $this->organization();
        $organization->loadMissing('settings');
        $schedule = $organization->settings?->billingSchedule() ?? [
            'auto_generation_enabled' => false,
            'billing_frequency' => BillingFrequency::MONTHLY->value,
            'invoice_generation_day' => 1,
            'reading_deadline_day' => 5,
            'payment_due_days' => 14,
            'send_created_notification' => true,
            'send_reminders' => true,
            'reminder_days_before_deadline' => [3, 1],
            'timezone' => 'UTC',
            'default_currency' => 'EUR',
        ];

        $this->billingForm = $schedule;
        $this->reminderDays = collect($schedule['reminder_days_before_deadline'] ?? [3, 1])
            ->implode(', ');
    }

    /**
     * @return list<int>
     */
    private function normalizedReminderDays(): array
    {
        return collect(explode(',', $this->reminderDays))
            ->map(fn (string $value): string => trim($value))
            ->filter(fn (string $value): bool => $value !== '' && is_numeric($value))
            ->map(fn (string $value): int => max(0, min(31, (int) $value)))
            ->unique()
            ->values()
            ->all();
    }

    private function organization(): Organization
    {
        $organization = $this->user()->organization;

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
