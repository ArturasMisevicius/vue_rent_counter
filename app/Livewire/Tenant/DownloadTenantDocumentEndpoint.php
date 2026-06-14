<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Actions\TenantDocuments\DownloadTenantDocument;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadTenantDocumentEndpoint extends Component
{
    public function download(
        Request $request,
        TenantDocument $tenantDocument,
        DownloadTenantDocument $downloadTenantDocument,
    ): StreamedResponse|Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $downloadTenantDocument->handle($tenantDocument, $user);
    }
}
