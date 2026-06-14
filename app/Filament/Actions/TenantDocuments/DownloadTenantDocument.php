<?php

declare(strict_types=1);

namespace App\Filament\Actions\TenantDocuments;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\TenantDocuments\TenantDocumentFile;
use App\Models\TenantDocument;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadTenantDocument
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(TenantDocument $document, User $actor): StreamedResponse|Response
    {
        Gate::forUser($actor)->authorize('download', $document);

        $disk = Storage::disk(TenantDocumentFile::DISK);

        abort_unless($disk->exists($document->file_path), 404);

        $this->auditLogger->record(
            AuditLogAction::EXPORTED,
            $document,
            [
                'context' => ['mutation' => 'tenant_document.downloaded'],
                'download' => [
                    'file_path' => $document->file_path,
                    'original_filename' => $document->original_filename,
                ],
            ],
            $actor->id,
            'Tenant document downloaded',
        );

        return $disk->download(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path),
            ['Content-Type' => $document->mime_type],
        );
    }
}
