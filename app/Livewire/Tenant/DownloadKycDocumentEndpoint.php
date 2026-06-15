<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Actions\TenantKyc\DownloadKycDocument;
use App\Models\TenantKycDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadKycDocumentEndpoint extends Component
{
    public function download(
        Request $request,
        TenantKycDocument $tenantKycDocument,
        DownloadKycDocument $downloadKycDocument,
    ): StreamedResponse|Response {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $downloadKycDocument->handle($tenantKycDocument, $user);
    }
}
