<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantKyc;

use App\Enums\AuditLogAction;
use App\Filament\Actions\TenantDocuments\DownloadTenantDocument;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\TenantKycDocument;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadKycDocument
{
    public function __construct(
        private readonly DownloadTenantDocument $downloadTenantDocument,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantKycDocument $document, User $actor): StreamedResponse|Response
    {
        Gate::forUser($actor)->authorize('download', $document);

        $document->loadMissing('fileDocument');

        abort_unless($document->fileDocument !== null, 404);

        $this->auditLogger->record(
            AuditLogAction::EXPORTED,
            $document,
            [
                'context' => ['mutation' => 'tenant_kyc_document.downloaded'],
                'download' => [
                    'file_document_id' => $document->file_document_id,
                    'document_type' => $document->document_type?->value,
                ],
            ],
            $actor->id,
            'Tenant KYC document downloaded',
        );

        return $this->downloadTenantDocument->handle($document->fileDocument, $actor);
    }
}
