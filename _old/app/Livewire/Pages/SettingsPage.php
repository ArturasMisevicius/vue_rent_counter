<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

final class SettingsPage extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewSettings');
    }

    public function render(): View
    {
        return view('pages.settings.index', [
            'stats' => [
                'database_size' => $this->getDatabaseSize(),
                'cache_size' => $this->getCacheSize(),
                'total_users' => User::count(),
                'total_properties' => Property::count(),
                'total_meters' => Meter::count(),
                'total_invoices' => Invoice::count(),
            ],
        ]);
    }

    private function getDatabaseSize(): string
    {
        $dbPath = database_path('database.sqlite');

        if (! file_exists($dbPath)) {
            return 'N/A';
        }

        $sizeInBytes = filesize($dbPath);
        $sizeInMB = round($sizeInBytes / 1024 / 1024, 2);

        return $sizeInMB.' MB';
    }

    private function getCacheSize(): string
    {
        $cachePath = storage_path('framework/cache/data');

        if (! is_dir($cachePath)) {
            return 'N/A';
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        $sizeInMB = round($size / 1024 / 1024, 2);

        return $sizeInMB.' MB';
    }
}
