<?php

namespace App\Filament\Pages;

use App\Models\IntegrationHealthCheck;
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
                    'checked_at',
                ])
                ->orderBy('label')
                ->get(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
