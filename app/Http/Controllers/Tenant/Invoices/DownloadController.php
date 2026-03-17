<?php

namespace App\Http\Controllers\Tenant\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(Invoice $invoice): StreamedResponse|Response
    {
        $this->authorize('download', $invoice);

        $disk = config('filesystems.default', 'local');

        abort_if(blank($invoice->document_path), 404);
        abort_unless(Storage::disk($disk)->exists($invoice->document_path), 404);

        return Storage::disk($disk)->download(
            $invoice->document_path,
            basename($invoice->document_path),
        );
    }
}
