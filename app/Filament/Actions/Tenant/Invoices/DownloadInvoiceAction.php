<?php

namespace App\Filament\Actions\Tenant\Invoices;

use App\Filament\Support\TenantKyc\TenantKycGate;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInvoiceAction
{
    public function __construct(
        private readonly TenantKycGate $tenantKycGate,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function handle(Invoice $invoice): StreamedResponse|Response
    {
        Gate::authorize('download', $invoice);

        $user = auth()->user();

        if ($user instanceof User && $user->isTenant() && $this->tenantKycGate->blocksInvoiceDownload($user)) {
            throw new AuthorizationException(__('tenant.pages.verification.invoice_download_blocked'));
        }

        abort_unless(filled($invoice->document_path), 404);

        $disk = Storage::disk(config('filesystems.default', 'local'));
        abort_unless($disk->exists($invoice->document_path), 404);

        return $disk->download(
            $invoice->document_path,
            basename($invoice->document_path),
        );
    }
}
