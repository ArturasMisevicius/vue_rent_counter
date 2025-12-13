<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\ExportService;
use App\Models\User;

/**
 * Job for generating exports in the background
 * 
 * Handles CSV, Excel, and PDF export generation that may take
 * time with large datasets, and optionally emails the results
 */
class ExportGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $exportType,
        public string $format,
        public array $filters = [],
        public ?int $userId = null,
        public bool $emailResults = false
    ) {
        $this->onQueue('exports');
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        Log::info('Starting export generation', [
            'type' => $this->exportType,
            'format' => $this->format,
            'user_id' => $this->userId,
        ]);

        try {
            $filePath = match ($this->exportType) {
                'organizations' => $this->exportOrganizations($exportService),
                'subscriptions' => $this->exportSubscriptions($exportService),
                'activity_logs' => $this->exportActivityLogs($exportService),
                'users' => $this->exportUsers($exportService),
                default => throw new \InvalidArgumentException("Unknown export type: {$this->exportType}")
            };

            Log::info('Export generation completed', [
                'type' => $this->exportType,
                'format' => $this->format,
                'file_path' => $filePath,
                'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            ]);

            // Email results if requested
            if ($this->emailResults && $this->userId) {
                $this->emailExportResults($filePath);
            }

        } catch (\Exception $e) {
            Log::error('Export generation failed', [
                'type' => $this->exportType,
                'format' => $this->format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Export organizations
     */
    private function exportOrganizations(ExportService $exportService): string
    {
        $query = $this->buildOrganizationsQuery();
        
        return match ($this->format) {
            'csv' => $exportService->exportOrganizationsCSV($query),
            'excel' => $exportService->exportOrganizationsExcel($query),
            default => throw new \InvalidArgumentException("Unsupported format: {$this->format}")
        };
    }

    /**
     * Export subscriptions
     */
    private function exportSubscriptions(ExportService $exportService): string
    {
        $query = $this->buildSubscriptionsQuery();
        
        return match ($this->format) {
            'csv' => $exportService->exportSubscriptionsCSV($query),
            'excel' => $exportService->exportSubscriptionsExcel($query),
            default => throw new \InvalidArgumentException("Unsupported format: {$this->format}")
        };
    }

    /**
     * Export activity logs
     */
    private function exportActivityLogs(ExportService $exportService): string
    {
        $query = $this->buildActivityLogsQuery();
        $startDate = isset($this->filters['date_from']) ? \Carbon\Carbon::parse($this->filters['date_from']) : null;
        $endDate = isset($this->filters['date_to']) ? \Carbon\Carbon::parse($this->filters['date_to']) : null;
        
        return match ($this->format) {
            'csv' => $exportService->exportActivityLogsCSV($query, $startDate, $endDate),
            'json' => $exportService->exportActivityLogsJSON($query, $startDate, $endDate),
            default => throw new \InvalidArgumentException("Unsupported format: {$this->format}")
        };
    }

    /**
     * Export users (placeholder - would need to implement in ExportService)
     */
    private function exportUsers(ExportService $exportService): string
    {
        // This would need to be implemented in ExportService
        throw new \Exception('User export not yet implemented');
    }

    /**
     * Build organizations query with filters
     */
    private function buildOrganizationsQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        if (empty($this->filters)) {
            return null;
        }

        $query = \App\Models\Organization::query();

        if (isset($this->filters['plan'])) {
            $query->where('plan', $this->filters['plan']);
        }

        if (isset($this->filters['is_active'])) {
            $query->where('is_active', $this->filters['is_active']);
        }

        if (isset($this->filters['suspended'])) {
            if ($this->filters['suspended']) {
                $query->whereNotNull('suspended_at');
            } else {
                $query->whereNull('suspended_at');
            }
        }

        if (isset($this->filters['created_from'])) {
            $query->where('created_at', '>=', $this->filters['created_from']);
        }

        if (isset($this->filters['created_to'])) {
            $query->where('created_at', '<=', $this->filters['created_to']);
        }

        return $query;
    }

    /**
     * Build subscriptions query with filters
     */
    private function buildSubscriptionsQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        if (empty($this->filters)) {
            return null;
        }

        $query = \App\Models\Subscription::query();

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['plan_type'])) {
            $query->where('plan_type', $this->filters['plan_type']);
        }

        if (isset($this->filters['expiring_soon'])) {
            if ($this->filters['expiring_soon']) {
                $query->whereBetween('expires_at', [
                    now()->toDateString(),
                    now()->addDays(14)->toDateString()
                ]);
            }
        }

        return $query;
    }

    /**
     * Build activity logs query with filters
     */
    private function buildActivityLogsQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        if (empty($this->filters)) {
            return null;
        }

        $query = \App\Models\OrganizationActivityLog::query();

        if (isset($this->filters['organization_id'])) {
            $query->where('organization_id', $this->filters['organization_id']);
        }

        if (isset($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (isset($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }

        return $query;
    }

    /**
     * Email export results to user
     */
    private function emailExportResults(string $filePath): void
    {
        try {
            $user = User::find($this->userId);
            if (!$user) {
                Log::warning('Cannot email export results - user not found', ['user_id' => $this->userId]);
                return;
            }

            $fileName = basename($filePath);
            $fileSize = filesize($filePath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            // Simple email notification (in production, you'd create a proper Mailable)
            Mail::raw(
                "Your {$this->exportType} export ({$this->format}) is ready.\n\n" .
                "File: {$fileName}\n" .
                "Size: {$fileSizeMB} MB\n" .
                "Generated: " . now()->format('Y-m-d H:i:s'),
                function ($message) use ($user, $filePath, $fileName) {
                    $message->to($user->email)
                           ->subject('Export Ready - ' . ucfirst($this->exportType))
                           ->attach($filePath, ['as' => $fileName]);
                }
            );

            Log::info('Export results emailed successfully', [
                'user_id' => $this->userId,
                'user_email' => $user->email,
                'file_name' => $fileName,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to email export results', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Export generation job failed', [
            'type' => $this->exportType,
            'format' => $this->format,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}