<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\ExpiringSubscriptionsWidget;
use App\Filament\Widgets\OrganizationStatsWidget;
use App\Filament\Widgets\PlatformUsageWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\SubscriptionStatsWidget;
use App\Filament\Widgets\SystemHealthWidget;
use App\Filament\Widgets\TopOrganizationsWidget;
use App\Services\DashboardCustomizationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.dashboard';

    public function mount(): void
    {
        // Handle URL-based layout import
        if (request()->has('import') && auth()->user()?->isSuperadmin()) {
            $this->handleLayoutImport(request()->get('import'));
        }
    }

    protected function handleLayoutImport(string $encodedConfig): void
    {
        try {
            $configuration = json_decode(base64_decode($encodedConfig), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid configuration format');
            }
            
            $customizationService = app(DashboardCustomizationService::class);
            
            if ($customizationService->importConfiguration(auth()->user(), $configuration)) {
                Notification::make()
                    ->title('Layout Imported')
                    ->body('Dashboard layout has been imported from the shared URL.')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Invalid configuration structure');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Failed')
                ->body('Failed to import layout from URL: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->role === UserRole::ADMIN || $user?->role === UserRole::MANAGER;
    }

    public function getWidgets(): array
    {
        $user = auth()->user();
        
        // Show different widgets based on user role
        if ($user?->isSuperadmin()) {
            // Use customization service for superadmin dashboard
            $customizationService = app(DashboardCustomizationService::class);
            return $customizationService->getEnabledWidgets($user);
        }
        
        // Default widgets for non-superadmin users
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getColumns(): array|int
    {
        $user = auth()->user();
        
        // Use 3-column grid for superadmin as per requirements
        if ($user?->isSuperadmin()) {
            $customizationService = app(DashboardCustomizationService::class);
            $configuration = $customizationService->getUserConfiguration($user);
            
            return $configuration['layout']['columns'] ?? [
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ];
        }
        
        return 2;
    }

    public function getHeaderWidgets(): array
    {
        $user = auth()->user();
        
        // Add customization component for superadmin
        if ($user?->isSuperadmin()) {
            return [
                \App\Livewire\DashboardCustomization::class,
            ];
        }
        
        return [];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        
        if (!$user?->isSuperadmin()) {
            return [];
        }

        return [
            Action::make('exportLayout')
                ->label('Export Layout')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (DashboardCustomizationService $customizationService) {
                    $configuration = $customizationService->exportConfiguration(auth()->user());
                    $filename = 'dashboard-layout-' . date('Y-m-d-H-i-s') . '.json';
                    
                    return response()->streamDownload(function () use ($configuration) {
                        echo json_encode($configuration, JSON_PRETTY_PRINT);
                    }, $filename, [
                        'Content-Type' => 'application/json',
                    ]);
                }),
                
            Action::make('importLayout')
                ->label('Import Layout')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('layout_file')
                        ->label('Layout File')
                        ->acceptedFileTypes(['application/json'])
                        ->required()
                        ->helperText('Upload a JSON file exported from another dashboard.')
                ])
                ->action(function (array $data, DashboardCustomizationService $customizationService) {
                    try {
                        $filePath = storage_path('app/' . $data['layout_file']);
                        
                        if (!file_exists($filePath)) {
                            throw new \Exception('File not found');
                        }
                        
                        $content = file_get_contents($filePath);
                        $configuration = json_decode($content, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('Invalid JSON format');
                        }
                        
                        if ($customizationService->importConfiguration(auth()->user(), $configuration)) {
                            Notification::make()
                                ->title('Layout Imported')
                                ->body('Dashboard layout has been successfully imported.')
                                ->success()
                                ->send();
                                
                            return redirect()->route('filament.admin.pages.dashboard');
                        } else {
                            throw new \Exception('Invalid configuration format');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Failed to import layout: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        // Clean up uploaded file
                        if (isset($filePath) && file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }),
                
            Action::make('resetDashboard')
                ->label('Reset Dashboard')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Reset Dashboard to Default')
                ->modalDescription('This will reset your dashboard to the default layout and remove all customizations. This action cannot be undone.')
                ->modalSubmitActionLabel('Reset Dashboard')
                ->action(function (DashboardCustomizationService $customizationService) {
                    if ($customizationService->resetToDefault(auth()->user())) {
                        Notification::make()
                            ->title('Dashboard Reset')
                            ->body('Your dashboard has been reset to the default layout.')
                            ->success()
                            ->send();
                            
                        return redirect()->route('filament.admin.pages.dashboard');
                    } else {
                        Notification::make()
                            ->title('Reset Failed')
                            ->body('Failed to reset dashboard. Please try again.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
