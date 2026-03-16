<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\OrganizationActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

/**
 * ExportService handles data export functionality for superadmin dashboard
 * 
 * Supports CSV and Excel exports for:
 * - Organizations with complete details and metrics
 * - Subscriptions with plan details, dates, limits, and renewal history
 * - Activity logs with date range selection and format options
 */
class ExportService
{
    /**
     * Export organizations to CSV format
     */
    public function exportOrganizationsCSV(?Builder $query = null): string
    {
        $organizations = $query ? $query->get() : Organization::all();
        
        $export = new OrganizationsExport($organizations);
        $filename = 'organizations_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        Excel::store($export, $filename, 'local', \Maatwebsite\Excel\Excel::CSV);
        
        return storage_path('app/' . $filename);
    }

    /**
     * Export organizations to Excel format with formatting
     */
    public function exportOrganizationsExcel(?Builder $query = null): string
    {
        $organizations = $query ? $query->get() : Organization::all();
        
        $export = new OrganizationsExport($organizations);
        $filename = 'organizations_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'local');
        
        return storage_path('app/' . $filename);
    }

    /**
     * Export subscriptions to CSV format
     */
    public function exportSubscriptionsCSV(?Builder $query = null): string
    {
        $subscriptions = $query ? $query->with(['user'])->get() : 
                        Subscription::with(['user'])->get();
        
        $export = new SubscriptionsExport($subscriptions);
        $filename = 'subscriptions_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        Excel::store($export, $filename, 'local', \Maatwebsite\Excel\Excel::CSV);
        
        return storage_path('app/' . $filename);
    }

    /**
     * Export subscriptions to Excel format with formatting
     */
    public function exportSubscriptionsExcel(?Builder $query = null): string
    {
        $subscriptions = $query ? $query->with(['user'])->get() : 
                        Subscription::with(['user'])->get();
        
        $export = new SubscriptionsExport($subscriptions);
        $filename = 'subscriptions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'local');
        
        return storage_path('app/' . $filename);
    }

    /**
     * Export activity logs to CSV format
     */
    public function exportActivityLogsCSV(?Builder $query = null, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $logs = $this->buildActivityLogsQuery($query, $startDate, $endDate)->get();
        
        $export = new ActivityLogsExport($logs);
        $filename = 'activity_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        Excel::store($export, $filename, 'local', \Maatwebsite\Excel\Excel::CSV);
        
        return storage_path('app/' . $filename);
    }

    /**
     * Export activity logs to JSON format
     */
    public function exportActivityLogsJSON(?Builder $query = null, ?Carbon $startDate = null, ?Carbon $endDate = null): string
    {
        $logs = $this->buildActivityLogsQuery($query, $startDate, $endDate)->get();
        
        $data = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'timestamp' => $log->created_at->toISOString(),
                'organization' => $log->organization?->name,
                'organization_id' => $log->organization_id,
                'user' => $log->user?->name,
                'user_id' => $log->user_id,
                'action' => $log->action,
                'resource_type' => $log->resource_type,
                'resource_id' => $log->resource_id,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
            ];
        });
        
        $filename = 'activity_logs_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/' . $filename);
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filepath;
    }

    /**
     * Build activity logs query with date filtering
     */
    private function buildActivityLogsQuery(?Builder $query = null, ?Carbon $startDate = null, ?Carbon $endDate = null): Builder
    {
        $logsQuery = $query ?: OrganizationActivityLog::with(['organization', 'user']);
        
        if ($startDate) {
            $logsQuery->where('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $logsQuery->where('created_at', '<=', $endDate);
        }
        
        return $logsQuery->orderBy('created_at', 'desc');
    }

    /**
     * Get export statistics
     */
    public function getExportStats(): array
    {
        return [
            'organizations_count' => Organization::count(),
            'subscriptions_count' => Subscription::count(),
            'activity_logs_count' => OrganizationActivityLog::count(),
            'last_export' => $this->getLastExportTime(),
        ];
    }

    /**
     * Get last export time from storage
     */
    private function getLastExportTime(): ?Carbon
    {
        $files = glob(storage_path('app/*_*.{csv,xlsx,json}'), GLOB_BRACE);
        
        if (empty($files)) {
            return null;
        }
        
        $latestFile = max(array_map('filemtime', $files));
        
        return Carbon::createFromTimestamp($latestFile);
    }
}

/**
 * Organizations Export Class
 */
class OrganizationsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected Collection $organizations;

    public function __construct(Collection $organizations)
    {
        $this->organizations = $organizations;
    }

    public function collection()
    {
        return $this->organizations;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Email',
            'Phone',
            'Domain',
            'Plan',
            'Status',
            'Is Active',
            'Suspended At',
            'Suspension Reason',
            'Max Properties',
            'Max Users',
            'Current Properties',
            'Current Users',
            'Trial Ends At',
            'Subscription Ends At',
            'Days Until Expiry',
            'Timezone',
            'Locale',
            'Currency',
            'Created At',
            'Updated At',
        ];
    }

    public function map($organization): array
    {
        return [
            $organization->id,
            $organization->name,
            $organization->slug,
            $organization->email,
            $organization->phone,
            $organization->domain,
            $organization->plan?->value ?? $organization->plan,
            $organization->getTenantStatus()->value,
            $organization->is_active ? 'Yes' : 'No',
            $organization->suspended_at?->format('Y-m-d H:i:s'),
            $organization->suspension_reason,
            $organization->max_properties,
            $organization->max_users,
            $organization->properties()->count(),
            $organization->users()->count(),
            $organization->trial_ends_at?->format('Y-m-d H:i:s'),
            $organization->subscription_ends_at?->format('Y-m-d H:i:s'),
            $organization->daysUntilExpiry(),
            $organization->timezone,
            $organization->locale,
            $organization->currency,
            $organization->created_at->format('Y-m-d H:i:s'),
            $organization->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 25,  // Name
            'C' => 20,  // Slug
            'D' => 30,  // Email
            'E' => 15,  // Phone
            'F' => 20,  // Domain
            'G' => 15,  // Plan
            'H' => 12,  // Status
            'I' => 10,  // Is Active
            'J' => 18,  // Suspended At
            'K' => 25,  // Suspension Reason
            'L' => 15,  // Max Properties
            'M' => 12,  // Max Users
            'N' => 18,  // Current Properties
            'O' => 15,  // Current Users
            'P' => 18,  // Trial Ends At
            'Q' => 20,  // Subscription Ends At
            'R' => 18,  // Days Until Expiry
            'S' => 15,  // Timezone
            'T' => 10,  // Locale
            'U' => 10,  // Currency
            'V' => 18,  // Created At
            'W' => 18,  // Updated At
        ];
    }
}

/**
 * Subscriptions Export Class
 */
class SubscriptionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected Collection $subscriptions;

    public function __construct(Collection $subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    public function collection()
    {
        return $this->subscriptions;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Organization',
            'User ID',
            'User Name',
            'User Email',
            'Plan Type',
            'Status',
            'Starts At',
            'Expires At',
            'Days Until Expiry',
            'Max Properties',
            'Max Tenants',
            'Is Active',
            'Is Expired',
            'Created At',
            'Updated At',
        ];
    }

    public function map($subscription): array
    {
        return [
            $subscription->id,
            $subscription->user?->tenant_id ? Organization::find($subscription->user->tenant_id)?->name ?? 'N/A' : 'N/A',
            $subscription->user_id,
            $subscription->user?->name,
            $subscription->user?->email,
            $subscription->plan_type,
            $subscription->status?->value ?? $subscription->status,
            $subscription->starts_at?->format('Y-m-d H:i:s'),
            $subscription->expires_at?->format('Y-m-d H:i:s'),
            $subscription->daysUntilExpiry(),
            $subscription->max_properties,
            $subscription->max_tenants,
            $subscription->isActive() ? 'Yes' : 'No',
            $subscription->isExpired() ? 'Yes' : 'No',
            $subscription->created_at->format('Y-m-d H:i:s'),
            $subscription->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 25,  // Organization
            'C' => 10,  // User ID
            'D' => 20,  // User Name
            'E' => 30,  // User Email
            'F' => 15,  // Plan Type
            'G' => 12,  // Status
            'H' => 18,  // Starts At
            'I' => 18,  // Expires At
            'J' => 18,  // Days Until Expiry
            'K' => 15,  // Max Properties
            'L' => 12,  // Max Tenants
            'M' => 10,  // Is Active
            'N' => 12,  // Is Expired
            'O' => 18,  // Created At
            'P' => 18,  // Updated At
        ];
    }
}

/**
 * Activity Logs Export Class
 */
class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected Collection $logs;

    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Timestamp',
            'Organization',
            'User',
            'Action',
            'Resource Type',
            'Resource ID',
            'IP Address',
            'User Agent',
            'Metadata',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->organization?->name ?? 'N/A',
            $log->user?->name ?? 'System',
            $log->action,
            $log->resource_type,
            $log->resource_id,
            $log->ip_address,
            $log->user_agent,
            json_encode($log->metadata),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 18,  // Timestamp
            'C' => 25,  // Organization
            'D' => 20,  // User
            'E' => 20,  // Action
            'F' => 15,  // Resource Type
            'G' => 12,  // Resource ID
            'H' => 15,  // IP Address
            'I' => 30,  // User Agent
            'J' => 40,  // Metadata
        ];
    }
}