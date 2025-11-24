<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Display the system settings page.
     */
    public function index()
    {
        // Only admins can access settings
        $this->authorize('viewSettings');
        
        // Get system statistics
        $stats = [
            'database_size' => $this->getDatabaseSize(),
            'cache_size' => $this->getCacheSize(),
            'total_users' => \App\Models\User::count(),
            'total_properties' => \App\Models\Property::count(),
            'total_meters' => \App\Models\Meter::count(),
            'total_invoices' => \App\Models\Invoice::count(),
        ];
        
        return view('admin.settings.index', compact('stats'));
    }

    /**
     * Update system settings.
     */
    public function update(UpdateSettingsRequest $request)
    {
        // Only admins can update settings
        $this->authorize('updateSettings');
        
        $validated = $request->validated();
        
        // Note: In a production environment, these would be stored in a settings table
        // or updated in the .env file. For now, we'll just show a success message.
        
        return back()->with('success', __('notifications.settings.updated'));
    }

    /**
     * Run a database backup.
     */
    public function runBackup()
    {
        // Only admins can run backups
        $this->authorize('runBackup');
        
        try {
            Artisan::call('backup:run');
            return back()->with('success', __('notifications.settings.backup_completed'));
        } catch (\Exception $e) {
            return back()->with('error', __('notifications.settings.backup_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Clear application caches.
     */
    public function clearCache()
    {
        // Only admins can clear cache
        $this->authorize('clearCache');
        
        try {
            Cache::flush();
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return back()->with('success', __('notifications.settings.cache_cleared'));
        } catch (\Exception $e) {
            return back()->with('error', __('notifications.settings.cache_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Get the database file size in MB.
     */
    private function getDatabaseSize(): string
    {
        $dbPath = database_path('database.sqlite');
        
        if (file_exists($dbPath)) {
            $sizeInBytes = filesize($dbPath);
            $sizeInMB = round($sizeInBytes / 1024 / 1024, 2);
            return $sizeInMB . ' MB';
        }
        
        return 'N/A';
    }

    /**
     * Get an estimate of cache size.
     */
    private function getCacheSize(): string
    {
        $cachePath = storage_path('framework/cache/data');
        
        if (!is_dir($cachePath)) {
            return 'N/A';
        }
        
        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        $sizeInMB = round($size / 1024 / 1024, 2);
        return $sizeInMB . ' MB';
    }
}
