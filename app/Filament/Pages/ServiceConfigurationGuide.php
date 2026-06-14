<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;

class ServiceConfigurationGuide extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'service-configuration-guide';

    protected string $view = 'filament.pages.service-configuration-guide';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdminLike();
    }

    public function getTitle(): string
    {
        return __('admin.service_configurations.guide.title');
    }

    /**
     * @return array<string, array{title: string, body: string}>
     */
    public function examples(): array
    {
        $examples = __('admin.service_configurations.guide.examples');

        return is_array($examples) ? $examples : [];
    }
}
