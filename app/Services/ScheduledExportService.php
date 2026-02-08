<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * ScheduledExportService handles automated export functionality
 * 
 * Provides scheduled exports with:
 * - Daily, weekly, and monthly export schedules
 * - Email delivery to superadmins
 * - Configurable export types and formats
 * - Automatic cleanup of old export files
 */
class ScheduledExportService
{
    protected ExportService $exportService;
    protected PdfReportService $pdfReportService;

    public function __construct(ExportService $exportService, PdfReportService $pdfReportService)
    {
        $this->exportService = $exportService;
        $this->pdfReportService = $pdfReportService;
    }

    /**
     * Execute daily scheduled exports
     */
    public function executeDailyExports(): array
    {
        $results = [];
        
        // Daily activity logs export
        $results['activity_logs'] = $this->exportDailyActivityLogs();
        
        // Daily executive summary
        $results['executive_summary'] = $this->generateDailyExecutiveSummary();
        
        return $results;
    }

    /**
     * Execute weekly scheduled exports
     */
    public function executeWeeklyExports(): array
    {
        $results = [];
        
        // Weekly organizations export
        $results['organizations'] = $this->exportWeeklyOrganizations();
        
        // Weekly subscriptions export
        $results['subscriptions'] = $this->exportWeeklySubscriptions();
        
        // Weekly activity summary
        $results['activity_summary'] = $this->exportWeeklyActivitySummary();
        
        return $results;
    }

    /**
     * Execute monthly scheduled exports
     */
    public function executeMonthlyExports(): array
    {
        $results = [];
        
        // Monthly comprehensive report
        $results['comprehensive_report'] = $this->generateMonthlyComprehensiveReport();
        
        // Monthly organizations export (Excel)
        $results['organizations_excel'] = $this->exportMonthlyOrganizationsExcel();
        
        // Monthly subscriptions export (Excel)
        $results['subscriptions_excel'] = $this->exportMonthlySubscriptionsExcel();
        
        return $results;
    }

    /**
     * Export daily activity logs
     */
    private function exportDailyActivityLogs(): array
    {
        $startDate = now()->subDay()->startOfDay();
        $endDate = now()->subDay()->endOfDay();
        
        try {
            $csvPath = $this->exportService->exportActivityLogsCSV(null, $startDate, $endDate);
            $jsonPath = $this->exportService->exportActivityLogsJSON(null, $startDate, $endDate);
            
            $this->emailExportToSuperadmins([
                'subject' => 'Daily Activity Logs Export - ' . $startDate->format('Y-m-d'),
                'body' => 'Please find attached the daily activity logs export for ' . $startDate->format('F j, Y'),
                'attachments' => [$csvPath, $jsonPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$csvPath, $jsonPath],
                'period' => $startDate->format('Y-m-d'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate daily executive summary
     */
    private function generateDailyExecutiveSummary(): array
    {
        try {
            $pdfPath = $this->pdfReportService->generateExecutiveSummary();
            
            $this->emailExportToSuperadmins([
                'subject' => 'Daily Executive Summary - ' . now()->format('Y-m-d'),
                'body' => 'Please find attached the daily executive summary report.',
                'attachments' => [$pdfPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$pdfPath],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export weekly organizations
     */
    private function exportWeeklyOrganizations(): array
    {
        try {
            $csvPath = $this->exportService->exportOrganizationsCSV();
            $pdfPath = $this->pdfReportService->generateOrganizationsReport();
            
            $this->emailExportToSuperadmins([
                'subject' => 'Weekly Organizations Report - Week of ' . now()->startOfWeek()->format('Y-m-d'),
                'body' => 'Please find attached the weekly organizations report.',
                'attachments' => [$csvPath, $pdfPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$csvPath, $pdfPath],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export weekly subscriptions
     */
    private function exportWeeklySubscriptions(): array
    {
        try {
            $csvPath = $this->exportService->exportSubscriptionsCSV();
            $pdfPath = $this->pdfReportService->generateSubscriptionsReport();
            
            $this->emailExportToSuperadmins([
                'subject' => 'Weekly Subscriptions Report - Week of ' . now()->startOfWeek()->format('Y-m-d'),
                'body' => 'Please find attached the weekly subscriptions report.',
                'attachments' => [$csvPath, $pdfPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$csvPath, $pdfPath],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export weekly activity summary
     */
    private function exportWeeklyActivitySummary(): array
    {
        $startDate = now()->subWeek()->startOfWeek();
        $endDate = now()->subWeek()->endOfWeek();
        
        try {
            $pdfPath = $this->pdfReportService->generateActivityLogsReport(null, $startDate, $endDate);
            
            $this->emailExportToSuperadmins([
                'subject' => 'Weekly Activity Summary - Week of ' . $startDate->format('Y-m-d'),
                'body' => 'Please find attached the weekly activity summary report for ' . 
                         $startDate->format('M j') . ' - ' . $endDate->format('M j, Y'),
                'attachments' => [$pdfPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$pdfPath],
                'period' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate monthly comprehensive report
     */
    private function generateMonthlyComprehensiveReport(): array
    {
        try {
            $executivePdf = $this->pdfReportService->generateExecutiveSummary();
            $organizationsPdf = $this->pdfReportService->generateOrganizationsReport();
            $subscriptionsPdf = $this->pdfReportService->generateSubscriptionsReport();
            
            $startDate = now()->subMonth()->startOfMonth();
            $endDate = now()->subMonth()->endOfMonth();
            $activityPdf = $this->pdfReportService->generateActivityLogsReport(null, $startDate, $endDate);
            
            $this->emailExportToSuperadmins([
                'subject' => 'Monthly Comprehensive Report - ' . $startDate->format('F Y'),
                'body' => 'Please find attached the comprehensive monthly reports for ' . $startDate->format('F Y'),
                'attachments' => [$executivePdf, $organizationsPdf, $subscriptionsPdf, $activityPdf],
            ]);
            
            return [
                'success' => true,
                'files' => [$executivePdf, $organizationsPdf, $subscriptionsPdf, $activityPdf],
                'period' => $startDate->format('F Y'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export monthly organizations Excel
     */
    private function exportMonthlyOrganizationsExcel(): array
    {
        try {
            $excelPath = $this->exportService->exportOrganizationsExcel();
            
            $this->emailExportToSuperadmins([
                'subject' => 'Monthly Organizations Data Export - ' . now()->subMonth()->format('F Y'),
                'body' => 'Please find attached the monthly organizations data export in Excel format.',
                'attachments' => [$excelPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$excelPath],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Export monthly subscriptions Excel
     */
    private function exportMonthlySubscriptionsExcel(): array
    {
        try {
            $excelPath = $this->exportService->exportSubscriptionsExcel();
            
            $this->emailExportToSuperadmins([
                'subject' => 'Monthly Subscriptions Data Export - ' . now()->subMonth()->format('F Y'),
                'body' => 'Please find attached the monthly subscriptions data export in Excel format.',
                'attachments' => [$excelPath],
            ]);
            
            return [
                'success' => true,
                'files' => [$excelPath],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Email export files to all superadmins
     */
    private function emailExportToSuperadmins(array $emailData): void
    {
        $superadmins = User::where('role', 'superadmin')->get();
        
        foreach ($superadmins as $superadmin) {
            Mail::send('emails.scheduled-export', [
                'user' => $superadmin,
                'subject' => $emailData['subject'],
                'body' => $emailData['body'],
            ], function ($message) use ($superadmin, $emailData) {
                $message->to($superadmin->email, $superadmin->name)
                        ->subject($emailData['subject']);
                
                foreach ($emailData['attachments'] as $attachment) {
                    if (file_exists($attachment)) {
                        $message->attach($attachment);
                    }
                }
            });
        }
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysToKeep = 30): array
    {
        $cutoffDate = now()->subDays($daysToKeep);
        $deletedFiles = [];
        
        $patterns = [
            'organizations_*.{csv,xlsx,pdf}',
            'subscriptions_*.{csv,xlsx,pdf}',
            'activity_logs_*.{csv,json,pdf}',
            'executive_summary_*.pdf',
        ];
        
        foreach ($patterns as $pattern) {
            $files = glob(storage_path('app/' . $pattern), GLOB_BRACE);
            
            foreach ($files as $file) {
                $fileTime = filemtime($file);
                
                if ($fileTime < $cutoffDate->timestamp) {
                    if (unlink($file)) {
                        $deletedFiles[] = basename($file);
                    }
                }
            }
        }
        
        return [
            'deleted_count' => count($deletedFiles),
            'deleted_files' => $deletedFiles,
            'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get scheduled export configuration
     */
    public function getExportConfiguration(): array
    {
        return [
            'daily_exports' => [
                'enabled' => true,
                'time' => '02:00',
                'timezone' => 'Europe/Vilnius',
                'types' => ['activity_logs', 'executive_summary'],
            ],
            'weekly_exports' => [
                'enabled' => true,
                'day' => 'monday',
                'time' => '03:00',
                'timezone' => 'Europe/Vilnius',
                'types' => ['organizations', 'subscriptions', 'activity_summary'],
            ],
            'monthly_exports' => [
                'enabled' => true,
                'day' => 1, // First day of month
                'time' => '04:00',
                'timezone' => 'Europe/Vilnius',
                'types' => ['comprehensive_report', 'organizations_excel', 'subscriptions_excel'],
            ],
            'cleanup' => [
                'enabled' => true,
                'retention_days' => 30,
                'schedule' => 'weekly',
            ],
        ];
    }

    /**
     * Update export configuration
     */
    public function updateExportConfiguration(array $config): bool
    {
        // In a real implementation, this would save to database or config file
        // For now, we'll just validate the structure
        
        $requiredKeys = ['daily_exports', 'weekly_exports', 'monthly_exports', 'cleanup'];
        
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                return false;
            }
        }
        
        // Additional validation could be added here
        
        return true;
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        $files = glob(storage_path('app/*_*.{csv,xlsx,pdf,json}'), GLOB_BRACE);
        
        $stats = [
            'total_files' => count($files),
            'total_size_mb' => 0,
            'by_type' => [],
            'by_date' => [],
        ];
        
        foreach ($files as $file) {
            $size = filesize($file);
            $stats['total_size_mb'] += $size / 1024 / 1024;
            
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $stats['by_type'][$extension] = ($stats['by_type'][$extension] ?? 0) + 1;
            
            $date = date('Y-m-d', filemtime($file));
            $stats['by_date'][$date] = ($stats['by_date'][$date] ?? 0) + 1;
        }
        
        $stats['total_size_mb'] = round($stats['total_size_mb'], 2);
        
        return $stats;
    }
}