<?php

declare(strict_types=1);

namespace App\Livewire\Superadmin;

use App\Filament\Support\Superadmin\Dashboard\PlatformDashboardData;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportRecentOrganizationsCsvEndpoint extends Component
{
    public function download(
        Request $request,
        ExportService $exportService,
        PlatformDashboardData $platformDashboardData,
    ): StreamedResponse {
        abort_unless($request->user()?->isSuperadmin(), 403);

        $export = $platformDashboardData->recentOrganizationsExport();

        return $exportService->streamCsv(
            filename: 'recently-created-organizations.csv',
            title: $export['title'],
            summary: [],
            columns: $export['columns'],
            rows: $export['rows'],
        );
    }
}
