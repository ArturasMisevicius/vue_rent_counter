<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Tenant\Widgets\AnomalyDetectionWidget;
use App\Filament\Tenant\Widgets\AuditChangeHistoryWidget;
use App\Filament\Tenant\Widgets\AuditOverviewWidget;
use App\Filament\Tenant\Widgets\AuditTrendsWidget;
use App\Filament\Tenant\Widgets\ComplianceStatusWidget;
use App\Filament\Tenant\Widgets\ConfigurationRollbackWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Livewire\DashboardCustomization;
use App\Services\DashboardCustomizationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = null;

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
                throw new \Exception(__('dashboard.layout.errors.invalid_config_format'));
            }

            $customizationService = app(DashboardCustomizationService::class);

            if ($customizationService->importConfiguration(auth()->user(), $configuration)) {
                Notification::make()
                    ->title(__('dashboard.layout.notifications.imported_title'))
                    ->body(__('dashboard.layout.notifications.imported_body'))
                    ->success()
                    ->send();
            } else {
                throw new \Exception(__('dashboard.layout.errors.invalid_config_structure'));
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('dashboard.layout.notifications.import_failed_title'))
                ->body(__('dashboard.layout.notifications.import_failed_body', [
                    'error' => $e->getMessage(),
                ]))
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

        // Default widgets for admin and manager users
        $widgets = [
            DashboardStatsWidget::class,
        ];

        // Add audit widgets for admin users
        if ($user?->role === UserRole::ADMIN) {
            $widgets = array_merge($widgets, [
                AuditOverviewWidget::class,
                AuditTrendsWidget::class,
                ComplianceStatusWidget::class,
                AnomalyDetectionWidget::class,
                AuditChangeHistoryWidget::class,
                ConfigurationRollbackWidget::class,
            ]);
        }

        return $widgets;
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
                DashboardCustomization::class,
            ];
        }

        return [];
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        if (! $user?->isSuperadmin()) {
            return [];
        }

        return [
            Action::make('exportLayout')
                ->label(__('dashboard.layout.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (DashboardCustomizationService $customizationService) {
                    $configuration = $customizationService->exportConfiguration(auth()->user());
                    $filename = 'dashboard-layout-'.date('Y-m-d-H-i-s').'.json';

                    return response()->streamDownload(function () use ($configuration) {
                        echo json_encode($configuration, JSON_PRETTY_PRINT);
                    }, $filename, [
                        'Content-Type' => 'application/json',
                    ]);
                }),

            Action::make('importLayout')
                ->label(__('dashboard.layout.actions.import'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('layout_file')
                        ->label(__('dashboard.layout.fields.layout_file'))
                        ->acceptedFileTypes(['application/json'])
                        ->required()
                        ->helperText(__('dashboard.layout.fields.layout_file_help')),
                ])
                ->action(function (array $data, DashboardCustomizationService $customizationService) {
                    try {
                        $filePath = storage_path('app/'.$data['layout_file']);

                        if (! file_exists($filePath)) {
                            throw new \Exception(__('dashboard.layout.errors.file_not_found'));
                        }

                        $content = file_get_contents($filePath);
                        $configuration = json_decode($content, true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception(__('dashboard.layout.errors.invalid_json'));
                        }

                        if ($customizationService->importConfiguration(auth()->user(), $configuration)) {
                            Notification::make()
                                ->title(__('dashboard.layout.notifications.imported_title'))
                                ->body(__('dashboard.layout.notifications.imported_body_file'))
                                ->success()
                                ->send();

                            return redirect()->route('filament.admin.pages.dashboard');
                        } else {
                            throw new \Exception(__('dashboard.layout.errors.invalid_config_structure'));
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('dashboard.layout.notifications.import_failed_title'))
                            ->body(__('dashboard.layout.notifications.import_failed_body_file', [
                                'error' => $e->getMessage(),
                            ]))
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
                ->label(__('dashboard.layout.actions.reset'))
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('dashboard.layout.modals.reset_heading'))
                ->modalDescription(__('dashboard.layout.modals.reset_description'))
                ->modalSubmitActionLabel(__('dashboard.layout.modals.reset_submit'))
                ->action(function (DashboardCustomizationService $customizationService) {
                    if ($customizationService->resetToDefault(auth()->user())) {
                        Notification::make()
                            ->title(__('dashboard.layout.notifications.reset_title'))
                            ->body(__('dashboard.layout.notifications.reset_body'))
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.pages.dashboard');
                    } else {
                        Notification::make()
                            ->title(__('dashboard.layout.notifications.reset_failed_title'))
                            ->body(__('dashboard.layout.notifications.reset_failed_body'))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getTitle(): string
    {
        return __('dashboard.navigation.title');
    }
}
