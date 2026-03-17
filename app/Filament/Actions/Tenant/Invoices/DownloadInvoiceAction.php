<?php

namespace App\Filament\Actions\Tenant\Invoices;

use App\Models\Invoice;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadInvoiceAction
{
    /**
     * @throws AuthorizationException
     */
    public function handle(Invoice $invoice): StreamedResponse|Response
    {
        Gate::authorize('download', $invoice);

        abort_unless(filled($invoice->document_path), 404);

        $disk = Storage::disk(config('filesystems.default', 'local'));
        abort_unless($disk->exists($invoice->document_path), 404);

        return $disk->download(
            $invoice->document_path,
            basename($invoice->document_path),
        );
    }
}
