<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Invoices\DownloadInvoiceAction;
use App\Models\Invoice;
use Illuminate\Http\Response;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadInvoiceEndpoint extends Component
{
    public function download(
        Invoice $invoice,
        DownloadInvoiceAction $downloadInvoiceAction,
    ): StreamedResponse|Response {
        return $downloadInvoiceAction->handle($invoice);
    }
}
