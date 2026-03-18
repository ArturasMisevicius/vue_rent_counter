<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Actions\Tenant\Invoices\DownloadInvoiceAction;
use App\Models\Invoice;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TenantInvoiceDownloadController extends Controller
{
    public function __invoke(
        Invoice $invoice,
        DownloadInvoiceAction $downloadInvoiceAction,
    ): StreamedResponse|Response {
        return $downloadInvoiceAction->handle($invoice);
    }
}
