<?php

namespace App\Filament\Pages;

use App\Filament\Actions\Superadmin\Integration\ResetIntegrationCircuitBreakerAction;
use App\Filament\Actions\Superadmin\Integration\RunIntegrationHealthChecksAction;
use App\Models\IntegrationHealthCheck;
use App\Models\SecurityViolation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class IntegrationHealth extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'integration-health';

    protected string $view = 'filament.pages.integration-health';

    public function getTitle(): string
    {
        return 'Integration Health';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getViewData(): array
    {
        return [
            'checks' => IntegrationHealthCheck::query()
                ->select([
                    'id',
                    'key',
                    'label',
                    'status',
                    'summary',
                    'details',
                    'response_time_ms',
                    'checked_at',
                ])
                ->orderBy('label')
                ->get(),
            'recentViolations' => SecurityViolation::query()
                ->select([
                    'id',
                    'organization_id',
                    'user_id',
                    'type',
                    'severity',
                    'ip_address',
                    'summary',
                    'metadata',
                    'occurred_at',
                ])
                ->with([
                    'organization:id,name',
                ])
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    public function checkNow(int $checkId): void
    {
        abort_unless(static::canAccess(), 403);

        $check = IntegrationHealthCheck::query()
            ->select(['id', 'key', 'label', 'status', 'summary', 'details', 'response_time_ms', 'checked_at'])
            ->findOrFail($checkId);

        app(RunIntegrationHealthChecksAction::class)->handle($check->key);

        Notification::make()
            ->title("{$check->label} checked")
            ->success()
            ->send();
    }

    public function resetCircuitBreaker(int $checkId): void
    {
        abort_unless(static::canAccess(), 403);

        $check = IntegrationHealthCheck::query()
            ->select(['id', 'key', 'label', 'status', 'summary', 'details', 'response_time_ms', 'checked_at'])
            ->findOrFail($checkId);

        app(ResetIntegrationCircuitBreakerAction::class)->handle($check);

        Notification::make()
            ->title("{$check->label} circuit breaker reset")
            ->success()
            ->send();
    }
}
