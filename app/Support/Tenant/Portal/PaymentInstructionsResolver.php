<?php

namespace App\Support\Tenant\Portal;

use App\Models\OrganizationSetting;

class PaymentInstructionsResolver
{
    public function resolve(?OrganizationSetting $settings): string
    {
        if (filled($settings?->payment_instructions)) {
            return $settings->payment_instructions;
        }

        if (filled($settings?->invoice_footer)) {
            return $settings->invoice_footer;
        }

        return 'Contact your building manager for payment instructions.';
    }
}
