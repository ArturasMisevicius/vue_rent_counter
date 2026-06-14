<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum HelpArticleCategory: string implements HasLabel
{
    use HasTranslatedLabel;

    case GETTING_STARTED = 'getting_started';
    case BILLING = 'billing';
    case READINGS = 'readings';
    case SERVICES = 'services';
    case INVOICES = 'invoices';
    case TENANTS = 'tenants';
    case CONTRACTS = 'contracts';
    case DOCUMENTS = 'documents';
    case TROUBLESHOOTING = 'troubleshooting';
}
