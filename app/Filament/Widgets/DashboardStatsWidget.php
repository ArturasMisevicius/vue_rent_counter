<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\UserResource;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * Actionable Dashboard Statistics Widget
 *
 * Provides role-based dashboard statistics with clickable navigation to
 * filtered resource views. Statistics are cached for performance and
 * automatically scoped by tenant and user role.
 *
 * ## Key Features
 * - Role-based statistics (Superadmin, Admin, Manager, Tenant)
 * - Clickable stats that navigate to filtered views
 * - Cached calculations with 5-minute TTL
 * - Tenant-scoped data access
 * - Visual feedback for interactive elements
 *
 * ## Actionable Statistics
 * - **Debt Widget**: Navigates to unpaid invoices
 * - **Active Tenants**: Navigates to active tenant list
 * - **Properties**: Navigates to property list
 * - **Buildings**: Navigates to building list
 * - **Recent Invoices**: Navigates to recent invoice list
 *
 * @package App\Filament\Widgets
 */
class DashboardStatsWidget extends CachedStatsWidget
{
    protected static ?int $sort = 1;
    
    protected int $cacheTtl = 300; // 5 minutes cache

    protected function calculateStats(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        return match ($user->role) {
            UserRole::SUPERADMIN => $this->getSuperadminStats(),
            UserRole::ADMIN => $this->getAdminStats($user),
            UserRole::MANAGER => $this->getManagerStats($user),
            UserRole::TENANT => $this->getTenantStats($user),
            default => [],
        };
    }

    private function getSuperadminStats(): array
    {
        $totalProperties = Property::count();
        $totalBuildings = Building::count();
        $activeUsers = User::whereNotNull('tenant_id')->count();
        $totalInvoices = Invoice::count();
        $unpaidInvoices = Invoice::where('status', 'unpaid')->count();

        return [
            $this->makeStatActionable(
                Stat::make('Total Properties', number_format($totalProperties))
                    ->description('Across all organizations')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('success'),
                PropertyResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Total Buildings', number_format($totalBuildings))
                    ->description('Across all organizations')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('info'),
                BuildingResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Active Users', number_format($activeUsers))
                    ->description('Active tenant users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('warning'),
                UserResource::class,
                ['tenant_id' => ['operator' => 'isNotNull']]
            ),
            
            $this->makeStatActionable(
                Stat::make('Outstanding Debt', number_format($unpaidInvoices))
                    ->description('Unpaid invoices')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                InvoiceResource::class,
                ['status' => ['value' => 'unpaid']]
            ),
        ];
    }

    private function getAdminStats(User $user): array
    {
        $totalProperties = Property::where('tenant_id', $user->tenant_id)->count();
        $totalBuildings = Building::where('tenant_id', $user->tenant_id)->count();
        $totalUsers = User::where('tenant_id', $user->tenant_id)->count();
        $recentInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $unpaidInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('status', 'unpaid')
            ->count();

        return [
            $this->makeStatActionable(
                Stat::make('Properties', number_format($totalProperties))
                    ->description('In your organization')
                    ->descriptionIcon('heroicon-m-home')
                    ->color('success'),
                PropertyResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Buildings', number_format($totalBuildings))
                    ->description('In your organization')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('info'),
                BuildingResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Team Members', number_format($totalUsers))
                    ->description('In your organization')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('warning'),
                UserResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Outstanding Debt', number_format($unpaidInvoices))
                    ->description('Unpaid invoices')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                InvoiceResource::class,
                ['status' => ['value' => 'unpaid']]
            ),
        ];
    }

    private function getManagerStats(User $user): array
    {
        $totalProperties = Property::where('tenant_id', $user->tenant_id)->count();
        $totalBuildings = Building::where('tenant_id', $user->tenant_id)->count();
        $recentInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $draftInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('status', 'draft')
            ->count();

        return [
            $this->makeStatActionable(
                Stat::make('Properties', number_format($totalProperties))
                    ->description('Under management')
                    ->descriptionIcon('heroicon-m-home')
                    ->color('success'),
                PropertyResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Buildings', number_format($totalBuildings))
                    ->description('Under management')
                    ->descriptionIcon('heroicon-m-building-office-2')
                    ->color('info'),
                BuildingResource::class
            ),
            
            $this->makeStatActionable(
                Stat::make('Recent Invoices', number_format($recentInvoices))
                    ->description('Last 30 days')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('primary'),
                InvoiceResource::class,
                ['created_at' => ['from' => now()->subDays(30)->format('Y-m-d')]]
            ),
            
            $this->makeStatActionable(
                Stat::make('Draft Invoices', number_format($draftInvoices))
                    ->description('Pending finalization')
                    ->descriptionIcon('heroicon-m-pencil-square')
                    ->color('warning'),
                InvoiceResource::class,
                ['status' => ['value' => 'draft']]
            ),
        ];
    }

    private function getTenantStats(User $user): array
    {
        $property = Property::where('tenant_id', $user->tenant_id)->first();
        $recentInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $unpaidInvoices = Invoice::where('tenant_id', $user->tenant_id)
            ->where('status', 'unpaid')
            ->count();

        $stats = [];

        if ($property) {
            $stats[] = Stat::make('Your Property', $property->address ?? 'Property')
                ->description('Assigned property')
                ->descriptionIcon('heroicon-m-home')
                ->color('success');
        } else {
            $stats[] = Stat::make('No Property', '0')
                ->description('No property assigned')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');
        }

        $stats[] = $this->makeStatActionable(
            Stat::make('Recent Invoices', number_format($recentInvoices))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            InvoiceResource::class,
            ['created_at' => ['from' => now()->subDays(30)->format('Y-m-d')]]
        );

        if ($unpaidInvoices > 0) {
            $stats[] = $this->makeStatActionable(
                Stat::make('Outstanding Balance', number_format($unpaidInvoices))
                    ->description('Unpaid invoices')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                InvoiceResource::class,
                ['status' => ['value' => 'unpaid']]
            );
        }

        return $stats;
    }
}